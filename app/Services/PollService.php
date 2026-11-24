<?php

namespace App\Services;

use App\Models\DesignOption;
use App\Models\DesignPoll;
use App\Models\DesignVote;
use App\Models\User;
use App\Support\Fail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** BPMN 3 — voting desain → PO desain pemenang. */
class PollService
{
    public function __construct(private NotificationService $notify) {}

    public function vote(DesignPoll $poll, DesignOption $option, User $user): void
    {
        Fail::unless($option->design_poll_id === $poll->id, 'Opsi tidak valid.');
        Fail::unless($poll->is_open, 'Voting ini tidak sedang berlangsung.');
        Fail::unless($poll->artistProfile->user_id !== $user->id, 'Artis tidak bisa memilih di voting miliknya sendiri.');
        Fail::unless(! DesignVote::where('design_poll_id', $poll->id)->where('user_id', $user->id)->exists(), 'Kamu sudah memberikan suara di voting ini (1 akun 1 suara).');

        try {
            DesignVote::create(['design_poll_id' => $poll->id, 'design_option_id' => $option->id, 'user_id' => $user->id, 'voted_at' => now()]);
        } catch (QueryException) {
            Fail::because('Kamu sudah memberikan suara di voting ini.');
        }
    }

    /** Satu kali saat voting benar-benar mulai, termasuk voting terjadwal. */
    public function announceOpening(DesignPoll $poll): bool
    {
        return DB::transaction(function () use ($poll) {
            $poll = DesignPoll::whereKey($poll->id)->lockForUpdate()->with('artistProfile')->firstOrFail();
            if (! $poll->is_open || $poll->open_notified_at || ! $poll->fandom_id) return false;
            $fandom = $poll->fandom_id;
            $favorite = DB::table('favorite_fandom')->where('fandom_id', $fandom)->pluck('user_id');
            $members = DB::table('fandom_member')->where('fandom_id', $fandom)->pluck('user_id');
            $pastVoters = DesignVote::whereIn('design_poll_id', DesignPoll::where('fandom_id', $fandom)->where('id', '!=', $poll->id)->select('id'))->pluck('user_id');
            $ids = $favorite->merge($members)->merge($pastVoters)->unique()->filter(fn ($id) => (int) $id !== (int) $poll->artistProfile->user_id);
            User::whereIn('id', $ids)->where('is_active', true)->orderBy('id')->chunkById(200, function ($users) use ($poll) {
                foreach ($users as $user) $this->notify->send($user, 'design_poll', 'Voting desain dibuka', "Voting \"{$poll->poll_title}\" dari {$poll->artistProfile->shop_name} sudah dibuka. Pilih desain favoritmu!", null, false);
            });
            $poll->update(['open_notified_at' => now()]);
            return true;
        });
    }

    /** Voting berakhir → tentukan pemenang (baca design_vote) + notifikasi. */
    public function finish(DesignPoll $poll): ?DesignOption
    {
        return DB::transaction(function () use ($poll) {
            $poll = DesignPoll::whereKey($poll->id)->lockForUpdate()->with('artistProfile')->firstOrFail();

            if ($poll->poll_status !== 'running') {
                return $poll->winner();
            }

            $poll->update(['poll_status' => 'completed']);
            $winner = $poll->winner();
            $votes = $winner?->votes()->count() ?? 0;

            $this->notify->send($poll->artistProfile->user_id, 'design_poll', 'Voting kamu selesai',
                "Voting \"{$poll->poll_title}\" selesai. Pemenang: " . ($winner?->description ?: 'Opsi #' . $winner?->id) . " ({$votes} suara). Buka PO desain pemenang dari Studio → Voting.");

            DesignVote::where('design_poll_id', $poll->id)->pluck('user_id')->unique()->each(fn ($uid) => $this->notify->send($uid, 'design_poll', 'Hasil voting desain',
                "Voting \"{$poll->poll_title}\" dari {$poll->artistProfile->shop_name} selesai. Desain pemenang: " . ($winner?->description ?: 'Opsi #' . $winner?->id) . '. Pantau PO-nya!', null, false));

            return $winner;
        });
    }

    /** Dipanggil saat produk PO dari desain pemenang dibuat → kabari pemilih. */
    public function announcePo(DesignOption $option, \App\Models\Product $product): void
    {
        $poll = $option->poll()->with('artistProfile')->first();

        DesignVote::where('design_poll_id', $poll->id)->pluck('user_id')->unique()->each(fn ($uid) => $this->notify->send($uid, 'design_poll', 'PO desain pemenang dibuka',
            "{$poll->artistProfile->shop_name} membuka PO \"{$product->product_name}\" dari hasil voting \"{$poll->poll_title}\". Ikut PO sebelum ditutup!"));
    }
}
