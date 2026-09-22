import { Html5Qrcode } from 'html5-qrcode';

// One live Html5Qrcode instance per reader element id - the
// barcode-scanner-modal Blade component gives each of its instances a
// unique id, so the same page can host more than one scanner (e.g. the
// product form's single-field scanner) without them colliding.
const instances = {};

window.BarcodeScanner = {
    /**
     * Starts the camera and calls onDecoded(text) for every barcode read.
     * The same code read again within ~1.5s is suppressed - a barcode
     * held in front of the camera gets decoded many times a second, and
     * without this a single physical scan would otherwise fire onDecoded
     * repeatedly.
     */
    async start(readerId, onDecoded) {
        if (instances[readerId]) {
            return;
        }

        const scanner = new Html5Qrcode(readerId);
        instances[readerId] = scanner;

        let lastCode = null;
        let lastAt = 0;

        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 150 } },
                (decodedText) => {
                    const now = Date.now();
                    if (decodedText === lastCode && now - lastAt < 1500) {
                        return;
                    }
                    lastCode = decodedText;
                    lastAt = now;
                    onDecoded(decodedText);
                },
                // Fires on every frame that *doesn't* decode a code - not
                // an error worth surfacing, the camera is just looking at
                // something that isn't a barcode yet.
                () => {},
            );
        } catch (err) {
            delete instances[readerId];
            onDecoded(null, err);
        }
    },

    async stop(readerId) {
        const scanner = instances[readerId];
        if (!scanner) {
            return;
        }
        delete instances[readerId];
        try {
            await scanner.stop();
            scanner.clear();
        } catch (err) {
            // Already stopped/torn down (e.g. the tab lost camera access) -
            // nothing left to clean up.
        }
    },
};
