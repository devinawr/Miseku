{{-- Mode terang/gelap: disimpan di localStorage per perangkat ('light' | 'dark' | 'system').
     Dipasang di <head> sebelum CSS supaya tidak berkedip putih saat halaman dibuka. --}}
<script>
    (function () {
        var media = window.matchMedia('(prefers-color-scheme: dark)');
        window.msGetMode = function () { try { return localStorage.getItem('ms-mode') || 'light'; } catch (e) { return 'light'; } };
        window.msApplyMode = function () {
            var m = window.msGetMode();
            document.documentElement.classList.toggle('dark', m === 'dark' || (m === 'system' && media.matches));
        };
        window.msSetMode = function (m) {
            try { localStorage.setItem('ms-mode', m); } catch (e) {}
            window.msApplyMode();
            window.dispatchEvent(new CustomEvent('ms-mode', { detail: m }));
        };
        if (!window.__msModeBound) {
            window.__msModeBound = true;
            media.addEventListener('change', window.msApplyMode);
            document.addEventListener('livewire:navigated', window.msApplyMode);
        }
        window.msApplyMode();
    })();
</script>
