/**
 * CurpCache - IndexedDB-based local cache for student CURP lookups.
 * Pre-loads all CURPs on scanner pages to enable instant client-side validation
 * and reduce server round-trips during QR scanning.
 */
const CurpCache = {
    DB_NAME: 'sm_curp_cache',
    STORE_NAME: 'students',
    DB_VERSION: 1,
    CACHE_DURATION: 60 * 60 * 1000, // 1 hour

    _db: null,

    async _open() {
        if (this._db) return this._db;

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);

            request.onerror = () => reject(request.error);

            request.onsuccess = () => {
                this._db = request.result;
                resolve(this._db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(this.STORE_NAME)) {
                    db.createObjectStore(this.STORE_NAME, { keyPath: 'curp' });
                }
            };
        });
    },

    /**
     * Fetch all CURPs from the server and store in IndexedDB.
     */
    async populate(apiUrl) {
        const response = await fetch(apiUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Error al cargar CURPs: ' + response.status);

        const students = await response.json();
        const db = await this._open();

        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.STORE_NAME, 'readwrite');
            const store = tx.objectStore(this.STORE_NAME);

            store.clear();

            for (const student of students) {
                store.put(student);
            }

            tx.oncomplete = () => {
                localStorage.setItem('curp_cache_ts', Date.now().toString());
                localStorage.setItem('curp_cache_count', students.length.toString());
                resolve(students.length);
            };

            tx.onerror = () => reject(tx.error);
        });
    },

    /**
     * Look up a CURP in the local cache. Returns student object or null.
     */
    async lookup(curp) {
        const db = await this._open();

        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.STORE_NAME, 'readonly');
            const store = tx.objectStore(this.STORE_NAME);
            const request = store.get(curp.trim().toUpperCase());

            request.onsuccess = () => resolve(request.result || null);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Check if the cache is still fresh (within CACHE_DURATION).
     */
    isFresh() {
        const ts = localStorage.getItem('curp_cache_ts');
        if (!ts) return false;
        return (Date.now() - parseInt(ts)) < this.CACHE_DURATION;
    },

    /**
     * Get the number of cached CURPs.
     */
    getCount() {
        return parseInt(localStorage.getItem('curp_cache_count') || '0');
    },

    /**
     * Initialize the cache: open DB and populate if stale.
     * Returns the number of cached CURPs.
     */
    async init(apiUrl) {
        await this._open();
        if (!this.isFresh()) {
            return await this.populate(apiUrl);
        }
        return this.getCount();
    },

    /**
     * Force-refresh the cache regardless of freshness.
     */
    async refresh(apiUrl) {
        return await this.populate(apiUrl);
    },
};

/**
 * Play a sound locally without a server round-trip (Web Audio API).
 */
function playLocalSound(type) {
    const sequences = {
        'success': [[660, 0.15], [880, 0.15]],
        'error':   [[440, 0.2],  [220, 0.3]],
        'warning': [[520, 0.15], [520, 0.15]],
    };

    const seq = sequences[type];
    if (!seq) return;

    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    if (ctx.state === 'suspended') ctx.resume();

    seq.forEach(([freq, dur], i) => {
        setTimeout(() => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, ctx.currentTime);
            gain.gain.setValueAtTime(0.12, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + dur);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + dur);
        }, i * 200);
    });
}
