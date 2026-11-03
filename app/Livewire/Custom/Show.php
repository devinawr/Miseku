<?php

namespace App\Livewire\Custom;

use App\Livewire\Concerns\RunsActions;
use App\Models\CustomOffer;
use App\Models\CustomRequest;
use App\Models\FileUpload;
use App\Services\NotificationService;
use App\Support\Fail;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Detail permintaan custom.
 * - Pembeli: lihat penawaran, tolak / pilih (→ checkout custom), batalkan permintaan.
 * - Artis: kirim / ubah / tarik penawaran (harga total, estimasi hari, catatan).
 *   1 artis = 1 penawaran per permintaan (UNIQUE custom_request_id + artist_profile_id):
 *   - menunggu jawaban → bisa diubah (mis. hasil diskusi harga 20k → 18k), pembeli dikabari;
 *   - ditarik sendiri → bisa dikirim lagi (baris yang sama dipakai ulang);
 *   - ditolak pembeli → tidak bisa menawar lagi untuk permintaan ini.
 * Artis yang membuka halaman ini memakai tampilan Studio (/studio/custom/{id}).
 */
#[Layout('components.layouts.app')]
class Show extends Component
{
    use RunsActions;

    public CustomRequest $customRequest;

    public $offer_price = null;

    public $estimated_days = 14;

    public string $note = '';

    /** true = form sedang dipakai untuk mengubah penawaran yang masih menunggu jawaban */
    public bool $editing = false;

    public function mount(CustomRequest $customRequest): void
    {
        $me = Auth::user();
        $isOwner = $customRequest->buyer_id === $me->id;
        $isArtist = $me->isArtist() && $me->artistProfile;
        abort_unless($isOwner || $isArtist || $me->isAdmin(), 404);
        $this->customRequest = $customRequest;

        // Artis (bukan pemilik permintaan) selalu di tampilan Studio; pemilik selalu di tampilan pembeli.
        if ($isArtist && ! $isOwner && request()->routeIs('custom.show')) {
            $this->redirectRoute('studio.custom.show', ['customRequest' => $customRequest->id], navigate: true);
        } elseif ($isOwner && request()->routeIs('studio.custom.show')) {
            $this->redirectRoute('custom.show', ['customRequest' => $customRequest->id], navigate: true);
        }
    }

    private function myOffer(): ?CustomOffer
    {
        $profile = Auth::user()->artistProfile;

        return $profile ? $this->customRequest->offers()->where('artist_profile_id', $profile->id)->latest('id')->first() : null;
    }

    public function submitOffer(): void
    {
        $profile = Auth::user()->artistProfile;
        abort_unless($profile && Auth::user()->isArtist(), 403);

        $this->validate([
            'offer_price'    => 'required|numeric|min:10000',
            'estimated_days' => 'required|integer|min:1|max:180',
            'note'           => 'nullable|string|max:1000',
        ], [], ['offer_price' => 'harga', 'estimated_days' => 'estimasi hari']);

        $wasEditing = $this->editing;
        $ok = $this->attempt(function () use ($profile) {
            $r = $this->customRequest->fresh();
            Fail::unless(in_array($r->request_status, ['open', 'offered'], true), 'Permintaan ini sudah tidak menerima penawaran.');
            Fail::unless($r->buyer_id !== Auth::id(), 'Tidak bisa menawar permintaanmu sendiri.');

            $existing = $r->offers()->where('artist_profile_id', $profile->id)->first();
            Fail::unless($existing?->offer_status !== 'rejected', 'Penawaranmu sudah ditolak pembeli, jadi tidak bisa menawar lagi untuk permintaan ini.');
            Fail::unless($existing?->offer_status !== 'selected', 'Penawaranmu sudah dipilih pembeli.');

            $data = ['offer_price' => $this->offer_price, 'estimated_days' => $this->estimated_days, 'note' => $this->note ?: null, 'offer_status' => 'pending'];
            $isUpdate = $existing?->offer_status === 'pending';
            $old = $existing ? (float) $existing->offer_price : null;

            // Baris yang sama dipakai ulang (ubah / kirim lagi setelah ditarik) karena UNIQUE per artis.
            $existing ? $existing->update($data) : CustomOffer::create($data + ['custom_request_id' => $r->id, 'artist_profile_id' => $profile->id]);
            $r->update(['request_status' => 'offered']);

            $price = Money::rp($this->offer_price);
            $isUpdate
                ? app(NotificationService::class)->send($r->buyer_id, 'custom', 'Penawaran custom diperbarui',
                    "{$profile->shop_name} mengubah penawaran \"{$r->design_character}\"" . ($old !== (float) $this->offer_price ? ' dari ' . Money::rp($old) : '') . " menjadi {$price} (estimasi {$this->estimated_days} hari).")
                : app(NotificationService::class)->send($r->buyer_id, 'custom', 'Penawaran custom baru',
                    "{$profile->shop_name} menawar \"{$r->design_character}\" seharga {$price} (estimasi {$this->estimated_days} hari).");
        }, $wasEditing ? 'Penawaran diperbarui, pembeli sudah dikabari.' : 'Penawaran terkirim ke pembeli.');

        if ($ok !== false) {
            $this->editing = false;
            $this->reset('offer_price', 'note');
        }
        $this->customRequest->refresh();
    }

    /** Buka form untuk mengubah penawaran yang masih menunggu jawaban (isi lama dimuat). */
    public function editOffer(): void
    {
        $offer = $this->myOffer();
        if (! $offer || $offer->offer_status !== 'pending') {
            return;
        }
        $this->offer_price = (int) $offer->offer_price;
        $this->estimated_days = $offer->estimated_days;
        $this->note = (string) $offer->note;
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->reset('offer_price', 'note');
        $this->resetValidation();
    }

    public function withdrawOffer(int $id): void
    {
        $offer = CustomOffer::where('artist_profile_id', Auth::user()->artistProfile?->id)->where('offer_status', 'pending')->findOrFail($id);
        $offer->update(['offer_status' => 'cancelled']);
        $this->editing = false;
        $this->syncStatus();
        $this->dispatch('toast', message: 'Penawaran ditarik.');
    }

    public function rejectOffer(int $id): void
    {
        abort_unless($this->customRequest->buyer_id === Auth::id(), 403);
        $offer = $this->customRequest->offers()->where('offer_status', 'pending')->with('artistProfile')->findOrFail($id);
        $offer->update(['offer_status' => 'rejected']);
        app(NotificationService::class)->send($offer->artistProfile->user_id, 'custom', 'Penawaran custom ditolak', "Pembeli menolak penawaranmu untuk \"{$this->customRequest->design_character}\".", null, false);
        $this->syncStatus();
        $this->dispatch('toast', message: 'Penawaran ditolak.');
    }

    public function choose(int $id): void
    {
        abort_unless($this->customRequest->buyer_id === Auth::id(), 403);
        $this->customRequest->offers()->where('offer_status', 'pending')->findOrFail($id);
        $this->redirectRoute('checkout', ['offer' => $id], navigate: true);
    }

    public function cancelRequest(): void
    {
        abort_unless($this->customRequest->buyer_id === Auth::id(), 403);
        $r = $this->customRequest;
        if (! in_array($r->request_status, ['open', 'offered'], true)) {
            return;
        }
        $r->offers()->where('offer_status', 'pending')->update(['offer_status' => 'cancelled', 'updated_at' => now()]);
        $r->update(['request_status' => 'cancelled']);
        $this->dispatch('toast', message: 'Permintaan custom dibatalkan.');
    }

    private function syncStatus(): void
    {
        $r = $this->customRequest->fresh();
        if (in_array($r->request_status, ['open', 'offered'], true)) {
            $r->update(['request_status' => $r->offers()->where('offer_status', 'pending')->exists() ? 'offered' : 'open']);
        }
        $this->customRequest = $r;
    }

    public function render()
    {
        $r = $this->customRequest->load(['merchandiseType', 'buyer', 'offers.artistProfile', 'order']);
        $me = Auth::user();
        $studio = $me->isArtist() && $me->artistProfile && $r->buyer_id !== $me->id;

        return view('livewire.custom.show', [
            'r'        => $r,
            'studio'   => $studio,
            'isOwner'  => $r->buyer_id === $me->id,
            'profile'  => $me->isArtist() ? $me->artistProfile : null,
            'myOffer'  => $this->myOffer(),
            // artis lain hanya melihat penawarannya sendiri
            'offers'   => ($r->buyer_id === $me->id || $me->isAdmin()) ? $r->offers->sortByDesc('id') : $r->offers->where('artist_profile_id', $me->artistProfile?->id)->sortByDesc('id'),
            'images'   => FileUpload::where('source_table', 'custom_request')->where('source_id', $r->id)->orderBy('sort_order')->get(),
        ])->title('Custom: ' . $r->design_character);
    }
}
