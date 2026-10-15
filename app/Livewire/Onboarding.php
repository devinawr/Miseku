<?php

namespace App\Livewire;

use App\Models\Fandom;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Onboarding setelah daftar — "Kamu suka fandom apa saja?"
 *
 * Jawaban disimpan ke tabel favorite_fandom dan langsung menjadi isi awal
 * vektor profil CBF (blok fandom), sehingga user baru tidak mengalami
 * cold start: beranda sudah bisa menampilkan rekomendasi sejak kunjungan pertama.
 */
#[Layout('components.layouts.app')]
#[Title('Pilih fandom favorit')]
class Onboarding extends Component
{
    /** @var array<int,int> id fandom terpilih */
    public array $selected = [];

    public string $filter = '';

    public function mount(): void
    {
        $this->selected = Auth::user()->favoriteFandoms()->pluck('fandom.id')->map(fn ($id) => (int) $id)->all();
    }

    public function toggle(int $fandomId): void
    {
        $this->selected = in_array($fandomId, $this->selected, true)
            ? array_values(array_diff($this->selected, [$fandomId]))
            : [...$this->selected, $fandomId];
    }

    #[Computed]
    public function fandoms()
    {
        return Fandom::active()
            ->when($this->filter !== '', fn ($q) => $q->where('fandom_name', 'like', '%' . $this->filter . '%'))
            ->withCount(['characters as karakter_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('fandom_name')
            ->get();
    }

    public function save()
    {
        $this->validate(
            ['selected' => ['required', 'array', 'min:1'], 'selected.*' => ['integer', 'exists:fandom,id']],
            ['selected.required' => 'Pilih minimal satu fandom dulu, ya.', 'selected.min' => 'Pilih minimal satu fandom dulu, ya.'],
        );

        // sync() = hapus yang tidak dipilih, tambah yang baru (pivot favorite_fandom)
        Auth::user()->favoriteFandoms()->syncWithPivotValues($this->selected, ['created_at' => now()]);

        return $this->finish();
    }

    public function skip()
    {
        return $this->finish(skipped: true);
    }

    private function finish(bool $skipped = false)
    {
        session()->flash('status', $skipped
            ? 'Oke! Kamu bisa atur fandom favorit kapan saja dari halaman akun.'
            : 'Beres! Rekomendasi di beranda sudah disesuaikan dengan fandom favoritmu.');

        $to = Auth::user()->isArtist() ? route('studio.dashboard') : route('home');

        return $this->redirect($to, navigate: true);
    }

    public function render()
    {
        return view('livewire.onboarding');
    }
}
