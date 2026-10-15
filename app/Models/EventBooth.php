<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Partisipasi artis di sebuah event (jembatan event ↔ artist_profile). */
class EventBooth extends Model
{
    protected $table = 'event_booth';

    protected $fillable = ['event_id', 'artist_profile_id', 'booth_number', 'operating_hours', 'attend_days'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function artistProfile(): BelongsTo
    {
        return $this->belongsTo(ArtistProfile::class, 'artist_profile_id');
    }

    /** attend_days (SET '1'..'7') → [1, 2] */
    public function getDaysAttribute(): array
    {
        return collect(explode(',', (string) $this->attend_days))->filter()->map(fn ($d) => (int) $d)->sort()->values()->all();
    }

    public function getLabelAttribute(): string
    {
        $days = collect($this->days)->map(fn ($d) => 'Day ' . $d)->join(', ');

        return $this->event->event_name . ' · Booth ' . ($this->booth_number ?: '(belum ada nomor)') . ($days ? ' · ' . $days : '');
    }
}
