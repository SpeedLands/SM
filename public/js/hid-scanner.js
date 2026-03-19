/**
 * HidScanner - WebHID implementation for QR/Barcode Scanners.
 * Supports multiple simultaneous devices.
 */
const HidScanner = {
    devices: new Map(), // Map<Device, String Buffer>
    onScan: null, // Callback function (curp) => { ... }

    getConnectedCount() {
        return this.devices.size;
    },

    getConnectedNames() {
        return Array.from(this.devices.keys()).map(d => d.productName).join(', ');
    },

    // HID Key Mapping (Usage Page 0x07)
    keyMap: {
        0x04: 'A', 0x05: 'B', 0x06: 'C', 0x07: 'D', 0x08: 'E', 0x09: 'F', 0x0A: 'G', 0x0B: 'H', 0x0C: 'I', 0x0D: 'J',
        0x0E: 'K', 0x0F: 'L', 0x10: 'M', 0x11: 'N', 0x12: 'O', 0x13: 'P', 0x14: 'Q', 0x15: 'R', 0x16: 'S', 0x17: 'T',
        0x18: 'U', 0x19: 'V', 0x1A: 'W', 0x1B: 'X', 0x1C: 'Y', 0x1D: 'Z',
        0x1E: '1', 0x1F: '2', 0x20: '3', 0x21: '4', 0x22: '5', 0x23: '6', 0x24: '7', 0x25: '8', 0x26: '9', 0x27: '0',
        0x28: 'ENTER', 0x2C: ' '
    },

    async connect() {
        if (!("hid" in navigator)) {
            alert("Tu navegador no soporta la API WebHID. Usa Chrome o Edge en PC.");
            return;
        }

        try {
            const requestedDevices = await navigator.hid.requestDevice({
                filters: []
            });

            if (requestedDevices.length > 0) {
                for (const device of requestedDevices) {
                    if (!this.devices.has(device)) {
                        await device.open();
                        this.devices.set(device, '');
                        device.addEventListener("inputreport", (event) => this.handleInputReport(event, device));
                        console.log("Scanner HID conectado:", device.productName);
                    }
                }
                localStorage.setItem('hid_scanner_auto_connect', 'true');
                return true;
            }
            return this.getConnectedCount() > 0;
        } catch (e) {
            console.error("Error al conectar escáner HID:", e);
        }
        return this.getConnectedCount() > 0;
    },

    async autoConnect() {
        if (localStorage.getItem('hid_scanner_auto_connect') !== 'true') return false;

        try {
            const availableDevices = await navigator.hid.getDevices();
            let connectedCount = 0;
            for (const device of availableDevices) {
                if (!device.opened) {
                    await device.open();
                    this.devices.set(device, '');
                    device.addEventListener("inputreport", (event) => this.handleInputReport(event, device));
                    console.log("Auto-conectado a escáner HID:", device.productName);
                    connectedCount++;
                }
            }
            return connectedCount > 0;
        } catch (e) {
            return false;
        }
    },

    handleInputReport(event, device) {
        const { data } = event;
        if (data.byteLength < 8) return;

        const keycodes = new Uint8Array(data.buffer, 2, 6);
        let buffer = this.devices.get(device) || '';

        keycodes.forEach(code => {
            if (code === 0) return;

            const char = this.keyMap[code];
            if (char === 'ENTER') {
                const finalVal = buffer.trim();
                if (finalVal.length > 0) {
                    if (this.onScan) this.onScan(finalVal);
                    window.dispatchEvent(new CustomEvent('hid-scan', { detail: { curp: finalVal } }));
                }
                buffer = '';
            } else if (char && char !== ' ') {
                buffer += char;
            }
        });

        this.devices.set(device, buffer);
    },

    disconnect() {
        for (const [device] of this.devices) {
            device.close();
        }
        this.devices.clear();
    }
};
