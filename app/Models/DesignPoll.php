<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Voting desain milik artis (BPMN 3). */
class DesignPoll extends Model
{
    protected $table = 'design_poll';

    protected $fillable = ['artist_profile_id', 'poll_title', 'description', 'start_date', 'end_date', 'poll_status'];

    protected function casts(): array
    {
        return ['start_date' => 'datetime', 'end_date' => 'datetime'];
    }

    public function artistProfile(): BelongsTo
    {
        return $this->belongsTo(ArtistProfile::class, 'artist_profile_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(DesignOption::class, 'design_poll_id')->orderBy('id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(DesignVote::class, 'design_poll_id');
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->poll_status === 'running' && now()->between($this->start_date, $this->end_date);
    }

    /** Opsi pemenang = suara terbanyak; seri → opsi yang lebih dulu dibuat. */
    public function winner(): ?DesignOption
    {
        return $this->options()->withCount('votes')->get()
            ->sortBy([['votes_count', 'desc'], ['id', 'asc']])->first();
    }

    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            $this->poll_status === 'cancelled' => 'Dibatalkan',
            $this->poll_status === 'completed' => 'Selesai',
            now()->lt($this->start_date)       => 'Belum mulai',
            now()->gt($this->end_date)         => 'Menunggu rekap',
            default                            => 'Berlangsung',
        };
    }
}
