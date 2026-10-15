<?php

namespace App\Livewire\Studio;

use App\Livewire\Concerns\RunsActions;
use App\Models\DesignOption;
use App\Models\DesignPoll;
use App\Services\PollService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Voting desain milik artis: buat voting (2–6 opsi), akhiri, buka PO desain pemenang. */
#[Layout('components.layouts.app')]
#[Title('Voting desain')]
class Polls extends Component
{
    use RunsActions, WithFileUploads;

    public bool $creating = false;

    public string $poll_title = '';

    public string $description = '';

    public string $start_date = '';

    public string $end_date = '';

    /** @var array<int, array{description:string}> */
    public array $options = [['description' => ''], ['description' => '']];

    public array $images = [];

    public function mount(): void
    {
        $this->start_date = now()->format('Y-m-d\TH:i');
        $this->end_date = now()->addDays(7)->format('Y-m-d\TH:i');
    }

    public function addOption(): void
    {
        if (count($this->options) < 6) {
            $this->options[] = ['description' => ''];
        }
    }

    public function removeOption(int $i): void
    {
        if (count($this->options) > 2) {
            unset($this->options[$i], $this->images[$i]);
            $this->options = array_values($this->options);
            $this->images = array_values($this->images);
        }
    }

    public function save(): void
    {
        $this->validate([
            'poll_title'            => 'required|string|max:150',
            'description'           => 'nullable|string|max:2000',
            'start_date'            => 'required|date',
            'end_date'              => 'required|date|after:start_date|after:now',
            'options'               => 'array|min:2|max:6',
            'options.*.description' => 'required|string|max:255',
            'images'                => 'array|size:' . count($this->options),
            'images.*'              => 'required|image|max:4096',
        ], ['images.size' => 'Setiap opsi wajib punya gambar desain.'], ['poll_title' => 'judul', 'end_date' => 'tanggal berakhir', 'options.*.description' => 'keterangan opsi', 'images.*' => 'gambar']);

        DB::transaction(function () {
            $poll = DesignPoll::create([
                'artist_profile_id' => Auth::user()->artistProfile->id, 'poll_title' => $this->poll_title, 'description' => $this->description ?: null,
                'start_date' => $this->start_date, 'end_date' => $this->end_date, 'poll_status' => 'running',
            ]);
            foreach ($this->options as $i => $op) {
                DesignOption::create(['design_poll_id' => $poll->id, 'design_image' => '/uploads/' . $this->images[$i]->store('polls', 'uploads'), 'description' => $op['description']]);
            }
        });

        $this->reset('creating', 'poll_title', 'description', 'images');
        $this->options = [['description' => ''], ['description' => '']];
        $this->dispatch('toast', message: 'Voting dibuat dan langsung tampil di halaman Voting.');
    }

    private function mine(int $id): DesignPoll
    {
        return DesignPoll::where('artist_profile_id', Auth::user()->artistProfile->id)->findOrFail($id);
    }

    public function finish(int $id): void
    {
        $this->attempt(fn () => app(PollService::class)->finish($this->mine($id)), 'Voting diakhiri, pemenang ditentukan.');
    }

    public function cancel(int $id): void
    {
        $poll = $this->mine($id);
        if ($poll->poll_status === 'running') {
            $poll->update(['poll_status' => 'cancelled']);
            $this->dispatch('toast', message: 'Voting dibatalkan.');
        }
    }

    public function render()
    {
        $polls = DesignPoll::where('artist_profile_id', Auth::user()->artistProfile->id)
            ->with(['options' => fn ($q) => $q->withCount('votes')->with('product')])->withCount('votes')->latest('id')->get();

        return view('livewire.studio.polls', compact('polls'));
    }
}
