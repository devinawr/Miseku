<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Master event (mis. Comifuro). Dibuat admin atau artis pertama. */
class Event extends Model
{
    protected $table = 'event';

    protected $fillable = ['event_name', 'location', 'start_date', 'end_date', 'created_by'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function booths(): HasMany
    {
        return $this->hasMany(EventBooth::class, 'event_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Event yang belum berakhir. */
    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->whereDate('end_date', '>=', today());
    }

    public function getDurationDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getIsOngoingAttribute(): bool
    {
        return today()->between($this->start_date, $this->end_date);
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->start_date->isSameDay($this->end_date)
            ? $this->start_date->translatedFormat('d M Y')
            : $this->start_date->translatedFormat('d M') . ' – ' . $this->end_date->translatedFormat('d M Y');
    }
}
