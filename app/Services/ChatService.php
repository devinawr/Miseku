<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\User;
use App\Support\Fail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Chat 1:1 (tanpa kolom "dibaca" di ERD, jadi tidak ada badge belum dibaca). */
class ChatService
{
    public function send(User $from, int $toId, string $body, array $context = [], bool $hasPhotos = false): ChatMessage
    {
        Fail::unless(trim($body) !== '' || $hasPhotos, 'Tulis pesan atau lampirkan foto dulu.');
        Fail::unless($toId !== $from->id, 'Tidak bisa mengirim chat ke diri sendiri.');
        Fail::unless(User::whereKey($toId)->where('is_active', true)->exists(), 'Penerima tidak ditemukan.');

        return ChatMessage::create([
            'sender_id'         => $from->id,
            'receiver_id'       => $toId,
            'customer_order_id' => $context['order'] ?? null,
            'custom_request_id' => $context['custom'] ?? null,
            'product_id'        => $context['product'] ?? null,
            'message_body'      => trim($body),
            'sent_at'           => now(),
        ]);
    }

    /** Daftar lawan bicara + pesan terakhir. */
    public function conversations(User $me): Collection
    {
        $rows = ChatMessage::query()
            ->selectRaw('CASE WHEN sender_id = ' . (int) $me->id . ' THEN receiver_id ELSE sender_id END AS partner_id, MAX(id) AS last_id')
            ->where(fn ($q) => $q->where('sender_id', $me->id)->orWhere('receiver_id', $me->id))
            ->groupBy(DB::raw('CASE WHEN sender_id = ' . (int) $me->id . ' THEN receiver_id ELSE sender_id END'))
            ->orderByDesc('last_id')
            ->get();

        $last = ChatMessage::whereIn('id', $rows->pluck('last_id'))->get()->keyBy('id');
        $users = User::with('artistProfile')->whereIn('id', $rows->pluck('partner_id'))->get()->keyBy('id');

        return $rows->map(fn ($r) => ['user' => $users->get($r->partner_id), 'last' => $last->get($r->last_id)])->filter(fn ($r) => $r['user']);
    }

    public function thread(User $me, int $partnerId): Collection
    {
        return ChatMessage::query()
            ->with(['images', 'product:id,product_name', 'order:id,order_code', 'customRequest:id,design_character'])
            ->where(fn ($q) => $q->where('sender_id', $me->id)->where('receiver_id', $partnerId))
            ->orWhere(fn ($q) => $q->where('sender_id', $partnerId)->where('receiver_id', $me->id))
            ->orderBy('sent_at')->orderBy('id')
            ->get();
    }
}
