{{-- PWA: daftarkan service worker + tangkap event "beforeinstallprompt" sedini mungkin
     (event ini bisa muncul sebelum Alpine siap). Tombol <x-install-app> memakai window.msInstall. --}}
<script>
    (function () {
        window.msInstall = {
            prompt: null,
            installed: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
            ios: /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream,
            available: function () { return !this.installed && (!!this.prompt || this.ios); },
            run: function () {
                if (this.prompt) {
                    var p = this.prompt;
                    p.prompt();
                    p.userChoice.finally(function () { window.msInstall.prompt = null; window.dispatchEvent(new CustomEvent('ms-install')); });
                    return 'prompt';
                }
                return this.ios ? 'ios' : 'manual';
            }
        };
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();               // tahan banner bawaan; tampilkan lewat tombol Miseku
            window.msInstall.prompt = e;
            window.dispatchEvent(new CustomEvent('ms-install'));
        });
        window.addEventListener('appinstalled', function () {
            window.msInstall.installed = true;
            window.msInstall.prompt = null;
            window.dispatchEvent(new CustomEvent('ms-install'));
        });
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
        }
    })();
</script>
