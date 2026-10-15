{{-- Tombol "Pasang aplikasi" (PWA). Tampil hanya bila browser mendukung & belum terpasang.
     Android/Chrome/Edge → dialog pasang bawaan. iPhone (Safari) → petunjuk "Tambah ke Layar Utama". --}}
@props(['variant' => 'menu'])
<div x-data="{ ok: window.msInstall && msInstall.available(), help: false }" x-on:ms-install.window="ok = msInstall.available()" x-show="ok" x-cloak {{ $attributes }}>
    @if ($variant === 'banner')
        <div x-data="{ hide: localStorage.getItem('ms-install-hide') === '1' }" x-show="!hide"
             class="ms-card flex items-center gap-3 p-4">
            <x-brand-mark class="h-11 w-11 shrink-0" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-extrabold text-ink-900">Pasang aplikasi Miseku</p>
                <p class="text-xs text-ink-500">Buka lebih cepat dari layar utama HP-mu, seperti aplikasi biasa.</p>
            </div>
            <button type="button" class="ms-btn-primary !px-4 !py-2 text-sm" @click="msInstall.run() === 'ios' && (help = true)">Pasang</button>
            <button type="button" class="ms-icon-btn" title="Tutup" @click="hide = true; localStorage.setItem('ms-install-hide', '1')"><x-icon name="x" class="h-5 w-5" /></button>
        </div>
    @else
        <button type="button" @click="msInstall.run() === 'ios' && (help = true)" class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm font-semibold text-ink-700 hover:bg-brand-50 hover:text-brand-700">
            <x-icon name="device" class="h-4.5 w-4.5" /> Pasang aplikasi
        </button>
    @endif

    {{-- Petunjuk iPhone / iPad --}}
    <template x-teleport="body">
        <div x-show="help" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-end justify-center bg-black/50 p-4 sm:items-center" @click.self="help = false">
            <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl">
                <p class="text-lg font-extrabold text-ink-900">Pasang Miseku di iPhone</p>
                <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm text-ink-700">
                    <li>Buka miseku di <b>Safari</b>.</li>
                    <li>Ketuk tombol <b>Bagikan</b> (kotak dengan panah ke atas).</li>
                    <li>Pilih <b>Tambah ke Layar Utama</b> → <b>Tambah</b>.</li>
                </ol>
                <button type="button" class="ms-btn-primary mt-5 w-full" @click="help = false">Mengerti</button>
            </div>
        </div>
    </template>
</div>
