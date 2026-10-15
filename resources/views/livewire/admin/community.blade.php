<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-admin-nav />
    <x-page-title title="Komunitas" subtitle="Tinjau laporan dari pengguna dan pantau diskusi di semua komunitas fandom. Setiap penghapusan wajib diberi alasan — penulis akan diberi tahu." />

    {{-- Tab --}}
    <div class="mb-6 flex gap-2">
        @foreach (['laporan' => 'Laporan', 'topik' => 'Semua topik'] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')"
                    class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold {{ $tab === $key ? 'bg-ink-900 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' }}">
                {{ $label }}
                @if ($key === 'laporan' && $pendingCount)
                    <span class="rounded-full bg-red-500 px-2 py-0.5 text-xs text-white">{{ $pendingCount }}</span>
                @endif
            </button>
        @endforeach
    </div>

    @if ($tab === 'laporan')
        {{-- ================= LAPORAN ================= --}}
        <div class="mb-4 flex gap-2 text-sm">
            @foreach (['pending' => 'Menunggu', 'removed' => 'Konten dihapus', 'dismissed' => 'Ditolak'] as $key => $label)
                <button type="button" wire:click="$set('reportStatus', '{{ $key }}')"
                        class="rounded-full px-3.5 py-1.5 font-bold {{ $reportStatus === $key ? 'bg-brand-600 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' }}">{{ $label }}</button>
            @endforeach
        </div>

        <div class="space-y-3">
            @forelse ($reports as $r)
                <div class="ms-card p-5" wire:key="rp-{{ $r->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="ms-chip bg-red-50 text-red-700"><x-icon name="flag" class="h-3.5 w-3.5" /> {{ $r->reason_label }}</span>
                                <span class="ms-chip ms-chip-muted">{{ $r->is_topic_report ? 'Topik' : 'Pesan' }}</span>
                                <span class="text-xs text-ink-500">{{ $r->created_at->translatedFormat('d M Y H:i') }}</span>
                            </p>
                            <p class="mt-2 text-sm text-ink-600">
                                Dilaporkan oleh <b class="text-ink-900">{{ $r->reporter?->name ?? '—' }}</b>
                                · penulis <b class="text-ink-900">{{ $r->reportedUser?->name ?? '—' }}</b>
                                @if ($r->topic) · di <b class="text-ink-900">{{ $r->topic->fandom?->fandom_name }}</b> — “{{ $r->topic->title }}” @endif
                            </p>
                        </div>
                        @if ($r->report_status === 'pending')
                            <div class="flex flex-wrap gap-2">
                                @if ($r->topic)
                                    <button type="button" wire:click="open({{ $r->discussion_topic_id }})" class="ms-btn-ghost">Lihat topik</button>
                                @endif
                                <button type="button" wire:click="askDismiss({{ $r->id }})" class="ms-btn-ghost">Tolak laporan</button>
                                <button type="button" wire:click="askDeleteFromReport({{ $r->id }})" class="ms-btn-primary !bg-red-600 hover:!bg-red-700"><x-icon name="trash" class="h-4.5 w-4.5" /> Hapus konten</button>
                            </div>
                        @endif
                    </div>

                    <div class="mt-3 rounded-xl bg-ink-50 p-3 text-sm whitespace-pre-line text-ink-700">{{ $r->reported_content }}</div>
                    @if ($r->reason_note)
                        <p class="mt-2 text-sm text-ink-600"><b>Keterangan pelapor:</b> {{ $r->reason_note }}</p>
                    @endif
                    @if ($r->report_status !== 'pending')
                        <p class="mt-2 text-sm {{ $r->report_status === 'removed' ? 'text-red-700' : 'text-ink-600' }}">
                            <b>{{ $r->report_status === 'removed' ? 'Dihapus' : 'Ditolak' }}</b> oleh {{ $r->handler?->name ?? 'admin' }}
                            · {{ $r->handled_at?->translatedFormat('d M Y H:i') }}
                            @if ($r->admin_note) — {{ $r->admin_note }} @endif
                        </p>
                    @endif
                </div>
            @empty
                <div class="ms-card p-10 text-center text-sm text-ink-500">
                    {{ $reportStatus === 'pending' ? 'Tidak ada laporan yang menunggu. 🎉' : 'Belum ada laporan di status ini.' }}
                </div>
            @endforelse
        </div>
    @else
        {{-- ================= SEMUA TOPIK ================= --}}
        <div class="ms-scroll-x mb-6 flex gap-2">
            <button type="button" wire:click="$set('filterFandom', '')"
                    class="shrink-0 rounded-xl px-3.5 py-2 text-sm font-bold {{ $filterFandom === '' ? 'bg-ink-900 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' }}">
                Semua fandom
            </button>
            @foreach ($fandoms as $f)
                <button type="button" wire:click="$set('filterFandom', '{{ $f->id }}')" wire:key="ff-{{ $f->id }}"
                        class="shrink-0 rounded-xl px-3.5 py-2 text-sm font-bold {{ (string) $filterFandom === (string) $f->id ? 'bg-ink-900 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100' }}">
                    {{ $f->fandom_name }} <span class="font-normal opacity-70">· {{ $f->members_count }} anggota · {{ $f->topics_count }} topik</span>
                </button>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
            <div class="ms-card overflow-hidden">
                <div class="border-b border-ink-100 p-4">
                    <input type="search" wire:model.live.debounce.300ms="q" class="ms-input" placeholder="Cari judul topik…">
                </div>
                <div class="max-h-[70vh] divide-y divide-ink-100 overflow-y-auto">
                    @forelse ($topics as $t)
                        <button type="button" wire:click="open({{ $t->id }})" wire:key="at-{{ $t->id }}"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-brand-50 {{ $topicId === $t->id ? 'bg-brand-50' : '' }}">
                            <x-avatar :user="$t->creator" class="h-9 w-9 shrink-0 text-sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-ink-900">{{ $t->title }}</p>
                                <p class="truncate text-xs text-ink-500">{{ $t->fandom?->fandom_name }} · oleh {{ $t->creator?->name }} · {{ $t->created_at->translatedFormat('d M Y') }}</p>
                            </div>
                            <span class="ms-chip ms-chip-muted shrink-0"><x-icon name="chat" class="h-3.5 w-3.5" /> {{ $t->messages_count }}</span>
                        </button>
                    @empty
                        <p class="px-4 py-10 text-center text-sm text-ink-500">Belum ada topik.</p>
                    @endforelse
                </div>
            </div>

            <div>
                @if ($topic)
                    <div class="ms-card p-5">
                        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-brand-700 uppercase">{{ $topic->fandom?->fandom_name }}</p>
                                <h2 class="text-xl font-extrabold text-ink-900">{{ $topic->title }}</h2>
                                <p class="text-xs text-ink-500">oleh {{ $topic->creator?->name }} · {{ $topic->created_at->translatedFormat('d M Y H:i') }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="askDelete('topic', {{ $topic->id }})" class="ms-btn-ghost text-red-600"><x-icon name="trash" class="h-4.5 w-4.5" /> Hapus topik</button>
                                <button type="button" wire:click="close" class="ms-btn-ghost"><x-icon name="x" class="h-4.5 w-4.5" /></button>
                            </div>
                        </div>
                        <div class="max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                            @forelse ($messages as $m)
                                <div class="rounded-xl border border-ink-100 p-4" wire:key="am-{{ $m->id }}">
                                    @include('partials.topic-message', ['m' => $m, 'mode' => 'admin'])
                                    @if ($m->replies->isNotEmpty())
                                        <div class="mt-4 space-y-3 border-l-2 border-brand-100 pl-4">
                                            @foreach ($m->replies as $rp)
                                                <div wire:key="ar-{{ $rp->id }}">@include('partials.topic-message', ['m' => $rp, 'mode' => 'admin'])</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="py-6 text-center text-sm text-ink-500">Topik ini belum punya pesan.</p>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="ms-card flex flex-col items-center justify-center p-10 text-center text-sm text-ink-500">
                        <x-icon name="chat" class="mb-3 h-8 w-8 text-ink-300" />
                        Pilih topik di sebelah kiri untuk melihat isi diskusinya.
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ================= Modal: hapus dengan alasan ================= --}}
    @if ($deleteType)
        <div class="fixed inset-0 z-[60] flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" wire:click.self="cancelDelete" wire:key="del-{{ $deleteType }}-{{ $deleteId }}">
            <form wire:submit="confirmDelete" class="w-full max-w-lg space-y-4 rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-red-600 uppercase">Hapus {{ $deleteType === 'topic' ? 'topik' : 'pesan' }}</p>
                        <p class="mt-1 text-lg font-extrabold text-ink-900">Kenapa konten ini dihapus?</p>
                    </div>
                    <button type="button" wire:click="cancelDelete" class="ms-icon-btn"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <label class="block">
                    <span class="ms-label">Alasan penghapusan</span>
                    <textarea wire:model="deleteReason" rows="3" maxlength="255" class="ms-input" placeholder="mis. Spam / promosi jual-beli akun di luar Miseku"></textarea>
                    @error('deleteReason')<p class="ms-error">{{ $message }}</p>@enderror
                </label>
                <p class="text-xs text-ink-500">
                    Alasan ini dikirim ke penulis lewat notifikasi.
                    {{ $deleteType === 'topic' ? 'Semua balasan di topik ini ikut terhapus.' : 'Balasan untuk pesan ini ikut terhapus.' }}
                    Foto lampiran juga dihapus permanen.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelDelete" class="ms-btn-ghost">Batal</button>
                    <button type="submit" class="ms-btn-primary !bg-red-600 hover:!bg-red-700" wire:loading.attr="disabled" wire:target="confirmDelete">Hapus</button>
                </div>
            </form>
        </div>
    @endif

    {{-- ================= Modal: tolak laporan ================= --}}
    @if ($dismissId)
        <div class="fixed inset-0 z-[60] flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" wire:click.self="$set('dismissId', null)" wire:key="dis-{{ $dismissId }}">
            <form wire:submit="confirmDismiss" class="w-full max-w-lg space-y-4 rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-lg font-extrabold text-ink-900">Tolak laporan ini?</p>
                    <button type="button" wire:click="$set('dismissId', null)" class="ms-icon-btn"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <label class="block">
                    <span class="ms-label">Catatan untuk pelapor (opsional)</span>
                    <textarea wire:model="dismissNote" rows="3" maxlength="255" class="ms-input" placeholder="mis. Pesan ini masih wajar dalam diskusi"></textarea>
                </label>
                <p class="text-xs text-ink-500">Konten tetap tampil. Pelapor diberi tahu bahwa laporannya sudah ditinjau.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('dismissId', null)" class="ms-btn-ghost">Batal</button>
                    <button type="submit" class="ms-btn-primary">Tolak laporan</button>
                </div>
            </form>
        </div>
    @endif
</div>
