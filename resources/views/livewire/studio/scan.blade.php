<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-studio-nav />
    <x-page-title title="Scan QR pick-up" subtitle="Hanya bisa saat event berlangsung. QR valid → pesanan selesai & dana dilepas ke saldo." />

    <div class="grid items-start gap-6 lg:grid-cols-2">
        <section class="ms-card space-y-4 p-5" x-data="qrScanner()">
            <div class="relative overflow-hidden rounded-2xl bg-ink-900" wire:ignore>
                <video x-ref="video" class="aspect-video w-full object-cover" playsinline muted x-show="running"></video>
                <canvas x-ref="canvas" class="hidden"></canvas>
                <div x-show="!running" class="flex aspect-video flex-col items-center justify-center gap-3 text-white/80">
                    <x-icon name="camera" class="h-10 w-10" />
                    <button type="button" class="ms-btn bg-white text-ink-900" @click="start()">Nyalakan kamera</button>
                </div>
            </div>
            <p class="text-sm text-red-600" x-show="error" x-text="error"></p>
            <button type="button" class="ms-btn-ghost w-full" x-show="running" @click="stop()">Matikan kamera</button>

            <form wire:submit="scan" class="flex gap-2">
                <input type="text" wire:model="code" class="ms-input flex-1 font-mono uppercase" placeholder="atau ketik kode MSK-…">
                <button type="submit" class="ms-btn-primary">Validasi</button>
            </form>

            @if ($result)
                <div class="rounded-2xl p-4 {{ $result['ok'] ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700' }}">
                    <p class="font-extrabold">{{ $result['ok'] ? '✓' : '✕' }} {{ $result['title'] }}</p>
                    <p class="font-mono text-sm">{{ $result['code'] }}</p>
                    @if ($result['ok'])<p class="mt-1 text-sm">{{ $result['buyer'] }} — {{ $result['items'] }}</p>@endif
                </div>
            @endif
        </section>

        <section class="space-y-4">
            <div class="ms-card p-5">
                <h3 class="font-extrabold text-ink-900">Menunggu diambil ({{ $waiting->count() }})</h3>
                <ul class="mt-2 divide-y divide-ink-100 text-sm">
                    @forelse ($waiting as $p)
                        <li class="flex justify-between gap-2 py-2"><span><b>{{ $p->order->order_code }}</b> · {{ $p->order->buyer->name }}</span><span class="text-xs text-ink-500">{{ $p->booth->event->event_name }}</span></li>
                    @empty
                        <li class="py-2 text-ink-500">Tidak ada.</li>
                    @endforelse
                </ul>
            </div>
            <div class="ms-card p-5">
                <h3 class="font-extrabold text-ink-900">Baru diambil</h3>
                <ul class="mt-2 divide-y divide-ink-100 text-sm">
                    @forelse ($recent as $p)
                        <li class="flex justify-between gap-2 py-2"><span><b>{{ $p->order->order_code }}</b> · {{ $p->order->buyer->name }}</span><span class="text-xs text-ink-500">{{ $p->picked_up_at?->translatedFormat('d M H:i') }}</span></li>
                    @empty
                        <li class="py-2 text-ink-500">Belum ada.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
</div>
