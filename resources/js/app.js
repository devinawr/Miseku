import './bootstrap';
import QRCode from 'qrcode';
import jsQR from 'jsqr';

// QR untuk pick-up event (halaman pesanan pembeli) & scanner kamera (Studio → Scan QR)
window.QRCode = QRCode;
window.jsQR = jsQR;

document.addEventListener('alpine:init', () => {
    // <canvas x-data="qrCanvas('MSK-...')"></canvas>
    window.Alpine.data('qrCanvas', (text) => ({
        init() {
            QRCode.toCanvas(this.$el, text, { width: 220, margin: 1, color: { dark: '#1e1b4b' } });
        },
    }));

    // Scanner kamera: memanggil $wire.scan(kode) saat QR terbaca
    window.Alpine.data('qrScanner', () => ({
        running: false,
        error: '',
        stream: null,
        last: '',
        async start() {
            this.error = '';
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            } catch (e) {
                this.error = 'Kamera tidak bisa dibuka (izin ditolak / perangkat tanpa kamera). Pakai input kode manual.';
                return;
            }
            const video = this.$refs.video;
            video.srcObject = this.stream;
            await video.play();
            this.running = true;
            this.tick();
        },
        stop() {
            this.running = false;
            this.stream?.getTracks().forEach((t) => t.stop());
            this.stream = null;
        },
        tick() {
            if (!this.running) return;
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(img.data, img.width, img.height);
                if (code && code.data && code.data !== this.last) {
                    this.last = code.data;
                    this.$wire.scan(code.data);
                    setTimeout(() => (this.last = ''), 3000);
                }
            }
            requestAnimationFrame(() => this.tick());
        },
        destroy() {
            this.stop();
        },
    }));
});
