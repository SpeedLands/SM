/**
 * CurpCache - Reusable IndexedDB utility for student identification.
 */
const CurpCache = {
    dbName: 'sm_curp_cache',
    dbVersion: 2,
    storeName: 'students',
    queueStoreName: 'pending_scans',
    db: null,

    async init(fetchUrl) {
        if (this.db) return this.count();

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onupgradeneeded = (e) => {
                const db = e.target.result;
                // Students Store
                if (!db.objectStoreNames.contains(this.storeName)) {
                    db.createObjectStore(this.storeName, { keyPath: 'id' });
                    const store = e.target.transaction.objectStore(this.storeName);
                    store.createIndex('curp', 'curp', { unique: true });
                }
                // Queue Store
                if (!db.objectStoreNames.contains(this.queueStoreName)) {
                    db.createObjectStore(this.queueStoreName, { keyPath: 'timestamp' });
                }
            };

            request.onsuccess = async (e) => {
                this.db = e.target.result;
                const count = await this.count();
                if (count === 0 && fetchUrl) {
                    await this.refresh(fetchUrl);
                }
                resolve(await this.count());
            };

            request.onerror = (e) => reject(e);
        });
    },

    async count() {
        if (!this.db) return 0;
        return new Promise((resolve) => {
            const tx = this.db.transaction(this.storeName, 'readonly');
            const store = tx.objectStore(this.storeName);
            const request = store.count();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => resolve(0);
        });
    },

    async refresh(fetchUrl) {
        if (!this.db) return;
        
        const response = await fetch(fetchUrl);
        const students = await response.json();

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            store.clear();

            students.forEach(s => store.put(s));

            tx.oncomplete = () => resolve(students.length);
            tx.onerror = (e) => reject(e);
        });
    },

    async lookup(curp) {
        if (!this.db) return null;
        curp = curp.trim().toUpperCase();
        
        return new Promise((resolve) => {
            const tx = this.db.transaction(this.storeName, 'readonly');
            const store = tx.objectStore(this.storeName);
            const index = store.index('curp');
            const request = index.get(curp);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => resolve(null);
        });
    },

    async destroy() {
        if (this.db) {
            this.db.close();
            this.db = null;
        }
        return new Promise((resolve, reject) => {
            const req = indexedDB.deleteDatabase(this.dbName);
            req.onsuccess = () => resolve();
            req.onerror = () => reject();
        });
    },

    // Queue Management
    async addToQueue(curp, studentInfo = null) {
        if (!this.db) return;
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.queueStoreName, 'readwrite');
            const store = tx.objectStore(this.queueStoreName);
            const item = {
                curp,
                studentInfo,
                timestamp: Date.now(),
                attempts: 0
            };
            store.put(item);
            tx.oncomplete = () => resolve(item);
            tx.onerror = (e) => reject(e);
        });
    },

    async getQueue() {
        if (!this.db) return [];
        return new Promise((resolve) => {
            const tx = this.db.transaction(this.queueStoreName, 'readonly');
            const store = tx.objectStore(this.queueStoreName);
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => resolve([]);
        });
    },

    async removeFromQueue(timestamp) {
        if (!this.db) return;
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.queueStoreName, 'readwrite');
            const store = tx.objectStore(this.queueStoreName);
            store.delete(timestamp);
            tx.oncomplete = () => resolve();
            tx.onerror = (e) => reject(e);
        });
    },

    async incrementAttempt(timestamp) {
        if (!this.db) return;
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(this.queueStoreName, 'readwrite');
            const store = tx.objectStore(this.queueStoreName);
            const request = store.get(timestamp);
            request.onsuccess = () => {
                const item = request.result;
                if (item) {
                    item.attempts = (item.attempts || 0) + 1;
                    store.put(item);
                    tx.oncomplete = () => resolve(item);
                } else {
                    resolve(null);
                }
            };
            request.onerror = (e) => reject(e);
        });
    },

    async requestPersistentStorage() {
        if (navigator.storage && navigator.storage.persist) {
            const isPersisted = await navigator.storage.persisted();
            if (!isPersisted) {
                const result = await navigator.storage.persist();
                console.log('Persistent storage granted:', result);
                return result;
            }
            return true;
        }
        return false;
    }
};

/**
 * Global sound utility using Web Audio API (No MP3s needed)
 */
function playLocalSound(type) {
    const sequences = {
        'success': [[660, 0.1], [880, 0.15]],
        'warning': [[520, 0.1], [520, 0.1]],
        'error':   [[440, 0.15], [220, 0.25]],
    };

    if (!sequences[type]) return;

    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContext();
        if (ctx.state === 'suspended') ctx.resume();

        sequences[type].forEach(([freq, dur], i) => {
            setTimeout(() => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, ctx.currentTime);
                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + dur);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + dur);
            }, i * 150);
        });
    } catch (e) {
        console.warn('Web Audio not supported:', e);
    }
}
