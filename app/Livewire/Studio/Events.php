<?php

namespace App\Livewire\Studio;

use App\Models\CustomerOrder;
use App\Models\Event;
use App\Models\EventBooth;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Event & booth (BPMN 5): artis mendaftarkan booth di event yang dibuat admin
 * (nomor booth, jam operasional, hari hadir). Booth inilah yang bisa dipilih
 * pembeli untuk pick-up dan dipakai untuk penjualan OTS.
 */
#[Layout('components.layouts.app')]
#[Title('Event & booth')]
class Events extends Component
{
    public ?int $editingEvent = null;

    public string $booth_number = '';

    public string $operating_hours = '10:00-21:00';

    public array $attend_days = [];

    public function edit(int $eventId): void
    {
        $booth = $this->booth($eventId);
        $this->editingEvent = $eventId;
        $this->booth_number = (string) $booth?->booth_number;
        $this->operating_hours = (string) ($booth?->operating_hours ?: '10:00-21:00');
        $event = Event::findOrFail($eventId);
        $this->attend_days = $booth ? array_map('strval', $booth->days) : array_map('strval', range(1, min(7, $event->duration_days)));
    }

    private function booth(int $eventId): ?EventBooth
    {
        return EventBooth::where('event_id', $eventId)->where('artist_profile_id', Auth::user()->artistProfile->id)->first();
    }

    public function save(): void
    {
        $event = Event::whereDate('end_date', '>=', today())->findOrFail($this->editingEvent);
        $this->validate([
            'booth_number'    => 'nullable|string|max:20',
            'operating_hours' => 'nullable|string|max:50',
            'attend_days'     => 'required|array|min:1',
            'attend_days.*'   => 'in:' . implode(',', range(1, min(7, $event->duration_days))),
        ], [], ['attend_days' => 'hari hadir']);

        EventBooth::updateOrCreate(
            ['event_id' => $event->id, 'artist_profile_id' => Auth::user()->artistProfile->id],
            ['booth_number' => $this->booth_number ?: null, 'operating_hours' => $this->operating_hours ?: null,
                'attend_days' => collect($this->attend_days)->map(fn ($d) => (int) $d)->sort()->implode(',')]
        );

        $this->editingEvent = null;
        $this->dispatch('toast', message: 'Booth tersimpan. Pembeli kini bisa memilih pick-up di event ini.');
    }

    public function remove(int $eventId): void
    {
        $booth = $this->booth($eventId);
        if (! $booth) {
            return;
        }
        if (CustomerOrder::where('event_booth_id', $booth->id)->exists()) {
            $this->dispatch('toast', message: 'Booth sudah dipakai pesanan, tidak bisa dihapus.', tone: 'error');

            return;
        }
        $booth->delete();
        $this->dispatch('toast', message: 'Booth dihapus.');
    }

    public function render()
    {
        $profileId = Auth::user()->artistProfile->id;
        $events = Event::whereDate('end_date', '>=', today()->subDays(30))->orderBy('start_date')->get();
        $booths = EventBooth::where('artist_profile_id', $profileId)->whereIn('event_id', $events->pluck('id'))->get()->keyBy('event_id');
        $pickups = CustomerOrder::where('artist_profile_id', $profileId)->where('shipping_method', 'event_pickup')
            ->whereIn('event_booth_id', $booths->pluck('id'))->selectRaw('event_booth_id, order_status, COUNT(*) AS n')
            ->groupBy('event_booth_id', 'order_status')->get()->groupBy('event_booth_id');

        return view('livewire.studio.events', compact('events', 'booths', 'pickups'));
    }
}
