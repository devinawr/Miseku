<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan konten komunitas dari pengguna (topik atau pesan).
 * reported_content menyimpan salinan isi saat dilaporkan, supaya catatan
 * tetap terbaca walaupun kontennya sudah dihapus admin.
 */
class CommunityReport extends Model
{
    protected $table = 'community_report';

    public const UPDATED_AT = null;

    public const REASONS = [
        'spam'       => 'Spam / promosi',
        'sara'       => 'SARA / ujaran kebencian',
        'pornografi' => 'Konten dewasa / pornografi',
        'penipuan'   => 'Penipuan',
        'pelecehan'  => 'Pelecehan / perundungan',
        'lainnya'    => 'Lainnya',
    ];

    protected $fillable = [
        'reporter_id', 'reported_user_id', 'discussion_topic_id', 'topic_message_id',
        'reason_category', 'reason_note', 'reported_content',
        'report_status', 'handled_by', 'admin_note', 'handled_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'handled_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(DiscussionTopic::class, 'discussion_topic_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(TopicMessage::class, 'topic_message_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason_category] ?? $this->reason_category;
    }

    /** Laporan untuk topik (bukan pesan tertentu). */
    public function getIsTopicReportAttribute(): bool
    {
        return $this->topic_message_id === null;
    }
}
