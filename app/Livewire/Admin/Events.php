<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\EventBooth;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Admin membuat & mengubah event (nama, lokasi, tanggal). Artis mendaftarkan booth sendiri. */
#[Layout('components.layouts.app')]
#[Title('Event')]
class Events extends Component
{
    public ?int $editingId = null;

    public string $event_name = '';

    public string $location = '';

    public string $start_date = '';

    public string $end_date = '';

    public function edit(?int $id = null): void
    {
        $e = $id ? Event::findOrFail($id) : null;
        $this->editingId = $id ?? 0;
        $this->event_name = (string) $e?->event_name;
        $this->location = (string) $e?->location;
        $this->start_date = $e?->start_date?->format('Y-m-d') ?? today()->addWeek()->format('Y-m-d');
        $this->end_date = $e?->end_date?->format('Y-m-d') ?? today()->addWeek()->addDay()->format('Y-m-d');
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $data = $this->validate([
            'event_name' => 'required|string|max:150',
            'location'   => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ], [], ['event_name' => 'nama event', 'location' => 'lokasi', 'end_date' => 'tanggal selesai']);

        if ($this->editingId) {
            Event::findOrFail($this->editingId)->update($data);
        } else {
            Event::create($data + ['created_by' => Auth::id()]);
        }
        $this->editingId = null;
        $this->dispatch('toast', message: 'Event tersimpan.');
    }

    public function delete(int $id): void
    {
        if (EventBooth::where('event_id', $id)->exists()) {
            $this->dispatch('toast', message: 'Event sudah punya booth artis, tidak bisa dihapus.', tone: 'error');

            return;
        }
        Event::whereKey($id)->delete();
        $this->dispatch('toast', message: 'Event dihapus.');
    }

    public function render()
    {
        return view('livewire.admin.events', [
            'events' => Event::with('booths.artistProfile')->orderByDesc('start_date')->get(),
        ]);
    }
}
