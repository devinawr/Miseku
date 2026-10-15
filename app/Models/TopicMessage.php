<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pesan dalam topik; balasan bertingkat lewat parent_message_id. */
class TopicMessage extends Model
{
    protected $table = 'topic_message';

    public $timestamps = false;

    protected $fillable = ['discussion_topic_id', 'parent_message_id', 'user_id', 'message_body', 'attached_product_id', 'attached_artist_id', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(DiscussionTopic::class, 'discussion_topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TopicMessage::class, 'parent_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TopicMessage::class, 'parent_message_id')->orderBy('sent_at');
    }

    public function attachedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'attached_product_id');
    }

    public function attachedArtist(): BelongsTo
    {
        return $this->belongsTo(ArtistProfile::class, 'attached_artist_id');
    }

    /** Foto lampiran (file_upload, source_table = 'topic_message'). */
    public function images(): HasMany
    {
        return $this->hasMany(FileUpload::class, 'source_id')->where('source_table', 'topic_message')->orderBy('sort_order');
    }
}
