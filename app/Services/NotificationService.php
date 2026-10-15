<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\FandomMember;
use App\Models\Product;
use App\Models\TrackTransaksi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifikasi in-app (tabel track_transaksi) + email pendamping.
 *
 * - Notifikasi transaksional disimpan (riwayat per pesanan).
 * - Notifikasi "produk baru sesuai minat" TIDAK disimpan — dihitung saat dibaca
 *   dari favorite_fandom + fandom_member (lihat newProductsFor()).
 * - Email dikirim lewat mailer Laravel (default MAIL_MAILER=log → tercatat di
 *   storage/logs/laravel.log). Hasilnya dicatat di email_status.
 */
class NotificationService
{
    public function send(User|int $to, string $type, string $title, string $body, ?CustomerOrder $order = null, bool $email = true): ?TrackTransaksi
    {
        $user = $to instanceof User ? $to : User::find($to);

        if (! $user || $user->email === config('miseku.ots_buyer_email')) {
            return null; // akun "Pembeli Umum (Event)" tidak menerima notifikasi
        }

        $note = TrackTransaksi::create([
            'user_id'           => $user->id,
            'customer_order_id' => $order?->id,
            'title'             => mb_substr($title, 0, 150),
            'body'              => $body,
            'notification_type' => $type,
            'is_read'           => false,
            'email_status'      => 'pending',
        ]);

        if ($email) {
            // Email dikirim setelah transaksi DB selesai supaya notifikasi tidak terkirim untuk data yang di-rollback.
            DB::afterCommit(fn () => $this->deliverEmail($note, $user));
        }

        return $note;
    }

    /** Kirim ke semua admin aktif (mis. pengajuan penarikan dana). */
    public function toAdmins(string $type, string $title, string $body): void
    {
        User::where('role', 'admin')->where('is_active', true)->get()
            ->each(fn (User $admin) => $this->send($admin, $type, $title, $body));
    }

    private function deliverEmail(TrackTransaksi $note, User $user): void
    {
        try {
            Mail::raw($note->body . "\n\n— Miseku\n" . config('app.url'), function ($m) use ($note, $user) {
                $m->to($user->email, $user->name)->subject($note->title . ' · Miseku');
            });
            $note->forceFill(['email_status' => 'sent'])->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('Email notifikasi gagal: ' . $e->getMessage());
            $note->forceFill(['email_status' => 'failed'])->saveQuietly();
        }
    }

    /**
     * Fandom yang diminati user = fandom favorit + fandom yang diikuti di komunitas.
     *
     * @return Collection<int,int>
     */
    public function interestFandomIds(User $user): Collection
    {
        return $user->favoriteFandoms()->pluck('fandom.id')
            ->merge(FandomMember::where('user_id', $user->id)->pluck('fandom_id'))
            ->map(fn ($id) => (int) $id)->unique()->values();
    }

    /** Produk baru (14 hari terakhir) dari fandom yang diminati — dihitung saat dibaca, tidak disimpan. */
    public function newProductsFor(User $user, int $days = 14, int $limit = 12): Collection
    {
        $fandoms = $this->interestFandomIds($user);

        if ($fandoms->isEmpty()) {
            return collect();
        }

        return Product::visible()
            ->with(['primaryImage', 'openBatch', 'artistProfile', 'merchandiseType', 'character.fandom'])
            ->whereHas('character', fn ($q) => $q->whereIn('fandom_id', $fandoms))
            ->whereHas('artistProfile', fn ($q) => $q->where('user_id', '!=', $user->id))
            ->where('created_at', '>=', now()->subDays($days))
            ->latest('created_at')
            ->take($limit)
            ->get();
    }

    /**
     * Email "produk baru sesuai minat" ke peminat fandom produk ini.
     * Sesuai desain, notifikasi ini tidak disimpan di track_transaksi.
     */
    public function announceNewProduct(Product $product): int
    {
        $product->loadMissing(['character.fandom', 'artistProfile']);
        $fandomId = $product->character?->fandom_id;

        if (! $fandomId) {
            return 0;
        }

        $userIds = DB::table('favorite_fandom')->where('fandom_id', $fandomId)->pluck('user_id')
            ->merge(FandomMember::where('fandom_id', $fandomId)->pluck('user_id'))
            ->unique()
            ->reject(fn ($id) => (int) $id === (int) $product->artistProfile->user_id);

        $sent = 0;
        User::whereIn('id', $userIds)->where('is_active', true)->get()->each(function (User $u) use ($product, &$sent) {
            try {
                Mail::raw(
                    "Halo {$u->name},\n\nAda produk baru dari fandom favoritmu ({$product->character->fandom->fandom_name}):\n"
                    . "{$product->product_name} oleh {$product->artistProfile->shop_name}.\n\n"
                    . route('product.detail', $product),
                    fn ($m) => $m->to($u->email, $u->name)->subject('Produk baru: ' . $product->product_name . ' · Miseku')
                );
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Email produk baru gagal: ' . $e->getMessage());
            }
        });

        return $sent;
    }

    /** URL tujuan saat notifikasi diklik (dihitung, tidak disimpan). */
    public function linkFor(TrackTransaksi $note, User $viewer): string
    {
        if ($note->customer_order_id && $note->order) {
            return $note->order->buyer_id === $viewer->id
                ? route('orders.show', $note->customer_order_id)
                : ($viewer->isAdmin() ? route('admin.transactions', ['q' => $note->order->order_code]) : route('studio.orders.show', $note->customer_order_id));
        }

        return match ($note->notification_type) {
            'withdrawal'  => $viewer->isAdmin() ? route('admin.withdrawals') : route('studio.balance'),
            'attribute'   => $viewer->isAdmin() ? route('admin.approvals') : route('studio.dashboard'),
            'custom'      => $viewer->isArtist() && ! str_contains($note->title, 'Penawaran') ? route('studio.custom') : route('custom.index'),
            'design_poll' => $viewer->isArtist() && str_contains($note->title, 'Voting kamu') ? route('studio.polls') : route('polls.index'),
            default       => route('notifications'),
        };
    }
}
