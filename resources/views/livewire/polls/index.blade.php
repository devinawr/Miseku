<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-title title="Voting desain" subtitle="Bantu artis memilih desain berikutnya. 1 akun 1 suara — desain pemenang dibuka sebagai pre-order." />

    @foreach (['Sedang berlangsung' => $running, 'Sudah selesai' => $finished] as $heading => $polls)
        <h2 class="ms-section-title mt-8 mb-4">{{ $heading }}</h2>
        @if ($polls->isEmpty())
            <p class="ms-card p-6 text-center text-sm text-ink-500">Belum ada voting.</p>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($polls as $poll)
                    <a href="{{ route('polls.show', $poll) }}" wire:navigate wire:key="p-{{ $poll->id }}" class="ms-card overflow-hidden transition hover:border-brand-300">
                        <div class="grid grid-cols-3 gap-0.5 bg-ink-100">
                            @foreach ($poll->options->take(3) as $op)<img src="{{ $op->design_image }}" class="aspect-square w-full object-cover" alt="">@endforeach
                        </div>
                        <div class="p-4">
                            <p class="text-xs font-bold text-accent-600 uppercase">{{ $poll->artistProfile->shop_name }}</p>
                            <p class="mt-0.5 font-extrabold text-ink-900">{{ $poll->poll_title }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ $poll->options->count() }} opsi · {{ $poll->votes_count }} suara · {{ $poll->status_label }}</p>
                            <p class="text-xs text-ink-400">Berakhir {{ $poll->end_date->translatedFormat('d M Y H:i') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    @endforeach
</div>
