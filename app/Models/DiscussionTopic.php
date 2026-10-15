<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscussionTopic extends Model
{
    protected $table = 'discussion_topic';

    protected $fillable = ['fandom_id', 'creator_id', 'title'];

    public function fandom(): BelongsTo
    {
        return $this->belongsTo(Fandom::class, 'fandom_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TopicMessage::class, 'discussion_topic_id');
    }
}
