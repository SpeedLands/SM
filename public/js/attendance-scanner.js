document.addEventListener('alpine:init', () => {
    Alpine.data('scannerComponent', (config) => ({
        localUseCamera: false,
        html5QrCode: null,
        scanCooldowns: {}, // Map of CURP -> Timestamp
        isStarting: false,
        curpInput: '',

        // Cache state
        cacheReady: false,
        cacheLoading: false,
        cacheCount: 0,
        cacheError: false,

        // UI state (Entangled)
        lastStudent: null,
        lastStatus: '',
        statusMessage: '',
        lastEntryTime: '',
        recentScans: [],
        
        hidConnected: 0,
        hidDeviceNames: '',
        
        // Queue state
        pendingQueue: [],
        isSyncingQueue: false,
        windowHasFocus: true,

        async init() {
            this.lastStudent = config.lastStudent;
            this.lastStatus = config.lastStatus;
            this.statusMessage = config.statusMessage;
            this.lastEntryTime = config.lastEntryTime;
            this.recentScans = config.recentScans;

            this.windowHasFocus = document.hasFocus();
            window.addEventListener('focus', () => this.windowHasFocus = true);
            window.addEventListener('blur', () => this.windowHasFocus = false);

            this.setupFocus();
            await this.loadCache(config.apiCurpsUrl);
            
            if (typeof CurpCache !== 'undefined') {
                await CurpCache.requestPersistentStorage();
            }
            
            await this.loadPendingQueue();
            
            // HID Listener
            window.addEventListener('hid-scan', (e) => {
                this.handleScan(e.detail.curp);
            });

            // Auto-connect HID if previously allowed
            if (typeof HidScanner !== 'undefined') {
                if (await HidScanner.autoConnect()) {
                    this.updateHidStatus();
                }
            }

            // Start initial sync if there's data
            this.processQueue(config.apiAttendanceUrl, config.csrfToken);

            // Listen for network becoming available
            window.addEventListener('online', () => {
                console.log('Internet restaurado. Intentando sincronizar...');
                this.processQueue(config.apiAttendanceUrl, config.csrfToken);
            });

            // Tries to sync every 15 seconds if there are pending items
            // Use setTimeout recursion to avoid blocking the main thread in a tight interval
            const scheduleSync = () => {
                setTimeout(() => {
                    if (this.pendingQueue.length > 0 && navigator.onLine) {
                        // Defer the actual sync work off the current call stack
                        queueMicrotask(() => this.processQueue(config.apiAttendanceUrl, config.csrfToken));
                    }
                    scheduleSync();
                }, 15000);
            };
            scheduleSync();
        },

        async loadPendingQueue() {
            if (typeof CurpCache === 'undefined') return;
            this.pendingQueue = await CurpCache.getQueue();
        },

        async loadCache(url) {
            if (typeof CurpCache === 'undefined') return;
            this.cacheLoading = true;
            try {
                this.cacheCount = await CurpCache.init(url);
                this.cacheReady = true;
            } catch (e) {
                console.warn('CURP cache init failed:', e);
                this.cacheError = true;
            }
            this.cacheLoading = false;
        },

        async refreshCache(url) {
            if (typeof CurpCache === 'undefined') return;
            this.cacheLoading = true;
            try {
                this.cacheCount = await CurpCache.refresh(url);
                this.cacheReady = true;
                this.cacheError = false;
            } catch (e) {
                console.warn('CURP cache refresh failed:', e);
            }
            this.cacheLoading = false;
        },

        async connectHID() {
            if (typeof HidScanner === 'undefined') return;
            if (await HidScanner.connect()) {
                this.updateHidStatus();
            }
        },

        updateHidStatus() {
            this.hidConnected = HidScanner.getConnectedCount();
            this.hidDeviceNames = HidScanner.getConnectedNames();
        },

        setupFocus() {
            const input = document.getElementById('scanner-input');
            document.addEventListener('click', () => {
                if (!this.localUseCamera) input?.focus();
            });
            // Use setTimeout 0 to defer focus so the keydown handler finishes quickly
            document.addEventListener('keydown', (e) => {
                if (this.localUseCamera) return;
                if (e.target !== input) setTimeout(() => input?.focus(), 0);
            });
        },

        async handleScan(decodedText) {
            const curp = (decodedText || this.curpInput || '').trim().toUpperCase();
            
            // CRITICAL: Clear input immediately to prevent concatenation
            this.curpInput = '';
            const input = document.getElementById('scanner-input');
            if (input) input.value = '';

            if (!curp) return;

            // Per-student cooldown
            const now = Date.now();
            if (this.scanCooldowns[curp] && (now - this.scanCooldowns[curp]) < 5000) {
                return;
            }
            this.scanCooldowns[curp] = now;

            // 1. Identification using local cache
            let studentInfo = null;
            if (this.cacheReady) {
                studentInfo = await CurpCache.lookup(curp);
            }

            if (studentInfo) {
                this.lastStudent = studentInfo;
                this.lastStatus = 'success';
                this.statusMessage = 'Identificado (Local)';
                this.lastEntryTime = new Date().toTimeString().slice(0, 8);
                if (window.playLocalSound) window.playLocalSound('success');
                
                // Add to recentScans for instant feedback
                let scansArray = Array.isArray(this.recentScans) ? this.recentScans : [];
                scansArray.unshift({
                    curp: curp,
                    name: studentInfo.name,
                    time: new Date().toTimeString().slice(0, 5),
                    status: 'PRESENTE',
                    color: 'green'
                });
                if (scansArray.length > 5) scansArray.pop();
                this.recentScans = scansArray;
            } else {
                this.lastStudent = null;
                this.lastStatus = 'error';
                this.statusMessage = 'CURP No reconocido (Buscando...)';
                if (window.playLocalSound) window.playLocalSound('warning');
            }

            // 2. Add to IndexedDB Queue
            const queueItem = await CurpCache.addToQueue(curp, studentInfo);
            this.pendingQueue.push(queueItem);

            // 3. Trigger Sync
            this.processQueue(config.apiAttendanceUrl, config.csrfToken);
        },

        async processQueue(url, csrf) {
            if (this.isSyncingQueue || this.pendingQueue.length === 0) return;
            this.isSyncingQueue = true;

            while (this.pendingQueue.length > 0) {
                const batch = this.pendingQueue.slice(0, 50);
                try {
                    const payload = batch.map(item => ({ curp: item.curp, timestamp: item.timestamp }));
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({ scans: payload })
                    });

                    if (!res.ok) {
                        if (res.status >= 400 && res.status !== 429 && res.status !== 503) {
                            throw new Error('ServerError');
                        }
                        throw new Error('Network error');
                    }

                    const data = await res.json();
                    let playError = false;
                    let playSuccess = false;
                    
                    for (const result of data.results) {
                        const itemIndex = this.pendingQueue.findIndex(q => q.timestamp === result.timestamp);
                        if (itemIndex > -1) {
                            const item = this.pendingQueue[itemIndex];
                            
                            if (result.status === 'error') {
                                // If the server explicitly says "error" but it's a structural error (not a temporary one)
                                // we might want to log it, but attendance is critical.
                                // For now, we only remove if the error is "not found" or similar permanent rejections.
                                console.error('Server rejected scan:', result.message, item.curp);
                                this.lastStatus = 'error';
                                this.statusMessage = 'Servidor rechazó registro: ' + item.curp;
                                playError = true;
                                
                                // Remove permanent errors to avoid clogging the queue
                                await CurpCache.removeFromQueue(item.timestamp);
                                this.pendingQueue.splice(itemIndex, 1);
                            } else {
                                this.lastStatus = result.duplicate ? 'duplicate' : (result.status === 'RETARDO' ? 'retardo' : 'success');
                                this.statusMessage = result.duplicate ? 'Ya registrado' : (result.status === 'RETARDO' ? 'RETARDO Registrado' : 'ASISTENCIA Registrada');
                                this.lastEntryTime = result.entry_time;
                                if (result.status !== 'error') playSuccess = true;
                                
                                // Update recent scan UI with server confirmation
                                const recentItem = this.recentScans.find(s => s.curp === item.curp);
                                if (recentItem) {
                                    recentItem.status = result.duplicate ? 'DUPLICADO' : result.status;
                                    recentItem.color = result.duplicate ? 'amber' : (result.status === 'RETARDO' ? 'orange' : 'green');
                                }

                                await CurpCache.removeFromQueue(item.timestamp);
                                this.pendingQueue.splice(itemIndex, 1);
                            }
                        }
                    }
                    
                    if (playError && window.playLocalSound) window.playLocalSound('error');
                    else if (playSuccess && window.playLocalSound) window.playLocalSound('success');

                } catch (e) {
                    console.warn('Sync failed (likely network/offline or 503):', e);
                    // CRITICAL: We DO NOT remove items from the queue on network failure or 503.
                    // We just break the loop and wait for the next auto-sync or network event.
                    
                    // We only increment attempts for analytics or if we want to eventually flag them,
                    // but we NEVER remove them unless the server confirms receipt or permanent failure.
                    if (typeof CurpCache !== 'undefined') {
                        for (const item of batch) {
                            await CurpCache.incrementAttempt(item.timestamp);
                        }
                    }
                    
                    // Wait 5 seconds before retrying (will break the while loop for this trigger)
                    await new Promise(resolve => { setTimeout(resolve, 5000); });
                    break;
                }
            }
            this.isSyncingQueue = false;
        },

        toggleCamera() {
            this.localUseCamera = !this.localUseCamera;
            if (this.localUseCamera) {
                this.isStarting = true;
                this.startCamera();
            } else {
                this.stopCamera();
            }
        },

        async startCamera() {
            if (typeof Html5Qrcode === 'undefined') return;
            this.html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            try {
                await this.html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => this.handleScan(decodedText)
                );
                this.isStarting = false;
            } catch (err) {
                console.error("Error starting camera", err);
                this.localUseCamera = false;
                this.isStarting = false;
                alert("No se pudo acceder a la cámara.");
            }
        },

        async stopCamera() {
            if (this.html5QrCode) {
                try {
                    await this.html5QrCode.stop();
                    this.html5QrCode = null;
                } catch (err) {
                    console.error("Error stopping camera", err);
                }
            }
        }
    }));
});
