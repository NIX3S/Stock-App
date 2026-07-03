/**
 * Scanner universel : BarcodeDetector natif (Chrome/Edge/Android) + ZXing-js
 * en repli automatique (Safari iOS, Firefox, anciens Android).
 * ZXing-js est chargé dynamiquement depuis CDN seulement si nécessaire,
 * pour ne pas alourdir les pages qui n'en ont pas besoin.
 */

const ZXING_CDN = 'https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.5/esm/index.js';

export class BarcodeScanner {
    /**
     * @param {HTMLVideoElement} videoElement
     * @param {function(string): void} onDetected  - appelé à chaque code trouvé
     */
    constructor(videoElement, onDetected) {
        this.video     = videoElement;
        this.onDetected = onDetected;
        this.stream    = null;
        this.running   = false;
        this._lastCode = null;      // anti-doublon : évite de déclencher 2x le même scan
        this._lastTs   = 0;
    }

    async start() {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } }
            });
        } catch (e) {
            throw new Error('Impossible d\'accéder à la caméra. Vérifiez les permissions du navigateur.');
        }

        this.video.srcObject = this.stream;
        await new Promise(res => { this.video.onloadedmetadata = res; });
        await this.video.play();
        this.running = true;

        if ('BarcodeDetector' in window) {
            this._startNative();
        } else {
            await this._startZXing();
        }
    }

    stop() {
        this.running = false;
        this.stream?.getTracks().forEach(t => t.stop());
        this._zxingReader?.reset?.();
    }

    _emit(code) {
        const now = Date.now();
        if (code === this._lastCode && now - this._lastTs < 2000) return;
        this._lastCode = code;
        this._lastTs   = now;
        this.onDetected(code);
    }

    // ---------- Moteur natif (Chrome, Edge, Android WebView) ----------
    _startNative() {
        const detector = new window.BarcodeDetector({
            formats: ['ean_13','ean_8','code_128','code_39','qr_code','upc_a','upc_e','itf','codabar']
        });
        const loop = async () => {
            if (!this.running) return;
            if (this.video.readyState === this.video.HAVE_ENOUGH_DATA) {
                try {
                    const codes = await detector.detect(this.video);
                    if (codes.length) this._emit(codes[0].rawValue);
                } catch (_) {}
            }
            requestAnimationFrame(loop);
        };
        requestAnimationFrame(loop);
    }

    // ---------- Repli ZXing-js (Safari iOS, Firefox…) ----------
    async _startZXing() {
        let ZXing;
        try {
            ZXing = await import(ZXING_CDN);
        } catch (e) {
            throw new Error('Impossible de charger la librairie de scan. Vérifiez votre connexion internet.');
        }

        const hints = new Map();
        hints.set(ZXing.DecodeHintType?.TRY_HARDER, true);

        const reader = new ZXing.BrowserMultiFormatReader(hints);
        this._zxingReader = reader;

        reader.decodeFromVideoElement(this.video, (result, err) => {
            if (result) this._emit(result.getText());
        });
    }
}

/**
 * Active un champ texte + bouton de scan.
 * @param {string} inputSelector  - sélecteur du champ code-barres
 * @param {string} btnSelector    - sélecteur du bouton déclencheur
 * @param {string} videoSelector  - sélecteur de l'élément <video>
 */
export function attachScanButton(inputSelector, btnSelector, videoSelector) {
    const input = document.querySelector(inputSelector);
    const btn   = document.querySelector(btnSelector);
    const video = document.querySelector(videoSelector);
    if (!input || !btn || !video) return;

    let scanner = null;

    btn.addEventListener('click', async () => {
        if (scanner) {
            scanner.stop();
            scanner = null;
            video.closest('.scanner-wrapper')?.classList.add('d-none');
            btn.textContent = '📷 Scanner';
            return;
        }

        video.closest('.scanner-wrapper')?.classList.remove('d-none');
        btn.textContent = '⏹ Arrêter';

        scanner = new BarcodeScanner(video, (code) => {
            input.value = code;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            scanner.stop();
            scanner = null;
            video.closest('.scanner-wrapper')?.classList.add('d-none');
            btn.textContent = '📷 Scanner';
        });

        try {
            await scanner.start();
        } catch (e) {
            alert(e.message);
            scanner = null;
            video.closest('.scanner-wrapper')?.classList.add('d-none');
            btn.textContent = '📷 Scanner';
        }
    });
}
