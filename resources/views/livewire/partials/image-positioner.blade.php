{{-- Pengaturan gambar di browser; file baru dikirim hanya setelah pengguna menyimpan. --}}
<div wire:ignore x-data="{
    kind: @js($kind), open: false, busy: false, error: '', url: '', image: null,
    width: 0, height: 0, zoom: 1, dx: 0, dy: 0, pointer: null,
    async choose(event) {
        const file = event.target.files[0]; event.target.value = '';
        if (!file) return;
        this.error = '';
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { this.error = 'Pilih gambar JPG, PNG, atau WEBP.'; return; }
        if (file.size > (this.kind === 'photo' ? 2 : 3) * 1024 * 1024) { this.error = 'Ukuran file terlalu besar.'; return; }
        const url = URL.createObjectURL(file);
        const img = new Image();
        try { img.src = url; await img.decode(); } catch (_) { URL.revokeObjectURL(url); this.error = 'Gambar tidak dapat dibuka.'; return; }
        if (this.kind === 'banner' && (img.naturalWidth < 800 || img.naturalHeight < 200)) { URL.revokeObjectURL(url); this.error = 'Ukuran banner minimal 800 × 200 piksel.'; return; }
        if (this.url) URL.revokeObjectURL(this.url);
        this.url = url; this.image = img; this.zoom = 1; this.dx = 0; this.dy = 0; this.open = true;
        this.$nextTick(() => setTimeout(() => this.measure(), 50));
    },
    measure() { if (!this.open) return; this.width = this.$refs.viewport.clientWidth; this.height = this.$refs.viewport.clientHeight; this.clamp(); },
    get scale() { return this.image && this.width && this.height ? Math.max(this.width / this.image.naturalWidth, this.height / this.image.naturalHeight) * Number(this.zoom) : 1; },
    clamp() {
        if (!this.image) return;
        const mx = Math.max(0, (this.image.naturalWidth * this.scale - this.width) / 2);
        const my = Math.max(0, (this.image.naturalHeight * this.scale - this.height) / 2);
        this.dx = Math.min(mx, Math.max(-mx, this.dx)); this.dy = Math.min(my, Math.max(-my, this.dy));
    },
    start(event) { if (this.busy) return; this.measure(); this.pointer = { id: event.pointerId, x: event.clientX, y: event.clientY, dx: this.dx, dy: this.dy }; event.currentTarget.setPointerCapture(event.pointerId); },
    move(event) { if (!this.pointer || this.pointer.id !== event.pointerId) return; this.dx = this.pointer.dx + event.clientX - this.pointer.x; this.dy = this.pointer.dy + event.clientY - this.pointer.y; this.clamp(); },
    stop(event) { if (this.pointer && this.pointer.id === event.pointerId) this.pointer = null; },
    nudge(event) { const keys = {ArrowLeft: [-10,0], ArrowRight: [10,0], ArrowUp: [0,-10], ArrowDown: [0,10]}; if (!keys[event.key]) return; event.preventDefault(); this.dx += keys[event.key][0]; this.dy += keys[event.key][1]; this.clamp(); },
    close() { if (this.busy) return; this.open = false; this.image = null; if (this.url) URL.revokeObjectURL(this.url); this.url = ''; this.pointer = null; },
    save() {
        this.measure();
        if (!this.image || !this.width || !this.height || this.busy) return;
        this.busy = true; this.error = '';
        const canvas = document.createElement('canvas'); canvas.width = this.kind === 'photo' ? 600 : 1500; canvas.height = this.kind === 'photo' ? 600 : 500;
        const ctx = canvas.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
        const scale = this.scale, sw = this.width / scale, sh = this.height / scale;
        const sx = (this.image.naturalWidth - sw) / 2 - this.dx / scale, sy = (this.image.naturalHeight - sh) / 2 - this.dy / scale;
        ctx.drawImage(this.image, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(blob => {
            if (!blob) { this.busy = false; this.error = 'Gambar tidak dapat diproses. Coba lagi.'; return; }
            const file = new File([blob], this.kind + '.jpg', {type: 'image/jpeg'});
            this.$wire.upload(this.kind, file, () => { this.busy = false; this.close(); }, () => { this.busy = false; this.error = 'Gagal mengunggah gambar. Coba lagi.'; });
        }, 'image/jpeg', 0.9);
    }
}" @resize.window="measure()" @keydown.escape.window="if (open) close()">
    <label class="ms-btn-soft cursor-pointer px-3 py-1.5 text-xs">
        <x-icon name="photo" class="h-4 w-4" /> {{ $label }}
        <input type="file" accept="image/jpeg,image/png,image/webp" @change="choose($event)" class="sr-only">
    </label>
    <p x-show="error && !open" x-text="error" class="ms-error" role="alert" x-cloak></p>
    <div x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-label="Atur posisi gambar" @click.self="close()">
        <div class="w-full max-w-2xl rounded-2xl bg-white p-5 shadow-xl sm:p-6">
            <h2 class="text-lg font-extrabold text-ink-900" x-text="kind === 'photo' ? 'Atur foto profil' : 'Atur banner toko'"></h2>
            <p class="mt-1 text-sm text-ink-600">Seret gambar untuk mengatur posisi. Gunakan penggeser untuk memperbesar.</p>
            <div x-ref="viewport" tabindex="0" aria-label="Pratinjau gambar, seret atau gunakan tombol panah untuk menggeser" @keydown="nudge($event)" @pointerdown.prevent="start($event)" @pointermove.prevent="move($event)" @pointerup="stop($event)" @pointercancel="stop($event)" class="relative mx-auto mt-4 overflow-hidden bg-ink-100" :class="kind === 'photo' ? 'w-64 max-w-full aspect-square rounded-full' : 'w-full aspect-[3/1] rounded-xl'" style="touch-action: none; cursor: grab;">
                <img :src="url" alt="Pratinjau gambar" draggable="false" class="pointer-events-none absolute max-w-none select-none" :style="image ? {width: (image.naturalWidth * scale) + 'px', height: (image.naturalHeight * scale) + 'px', left: ((width - image.naturalWidth * scale) / 2 + dx) + 'px', top: ((height - image.naturalHeight * scale) / 2 + dy) + 'px'} : {}">
            </div>
            <label class="mt-4 flex items-center gap-3 text-sm font-semibold text-ink-700">Perbesar <input type="range" min="1" max="3" step="0.01" x-model.number="zoom" @input="clamp()" class="w-full" :disabled="busy"></label>
            <p x-show="error" x-text="error" class="ms-error mt-2" role="alert" x-cloak></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="ms-btn-ghost" @click="close()" :disabled="busy">Batal</button>
                <button type="button" class="ms-btn-primary" @click="save()" :disabled="busy" x-text="busy ? 'Menyimpan…' : 'Simpan gambar'"></button>
            </div>
        </div>
    </div>
</div>
