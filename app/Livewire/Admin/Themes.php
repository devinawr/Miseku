<?php

namespace App\Livewire\Admin;

use App\Models\Fandom;
use App\Models\FandomTheming;
use App\Support\ThemePalette;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Admin mengelola skin / tema per fandom (tabel fandom_theming):
 * warna utama, warna gradasi, warna aksen, banner, dan status aktif.
 * Pratinjau langsung + cek kontras supaya tombol tetap terbaca.
 */
#[Layout('components.layouts.app')]
#[Title('Tema fandom')]
class Themes extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public string $fandom_id = '';

    public string $theming_name = '';

    public string $primary_color = '#7c3aed';

    public string $secondary_color = '#db2777';

    public string $accent_color = '';

    public bool $is_active = true;

    public $banner = null;

    public ?string $currentBanner = null;

    public bool $removeBanner = false;

    public string $filterFandom = '';

    /** Palet cepat di form: isi 3 warna sekaligus (masih bisa diubah manual). */
    public function applyPreset(string $primary, string $secondary, string $accent): void
    {
        foreach (compact('primary', 'secondary', 'accent') as $v) {
            abort_unless(preg_match('/^#[0-9a-fA-F]{6}$/', $v), 422);
        }
        $this->primary_color = $primary;
        $this->secondary_color = $secondary;
        $this->accent_color = $accent;
    }

    public function edit(?int $id = null): void
    {
        $t = $id ? FandomTheming::findOrFail($id) : null;
        $this->editingId = $id ?? 0;
        $this->fandom_id = (string) ($t?->fandom_id ?? $this->filterFandom);
        $this->theming_name = (string) $t?->theming_name;
        $this->primary_color = $t?->primary_color ?? '#7c3aed';
        $this->secondary_color = $t?->secondary_color ?? '#db2777';
        $this->accent_color = (string) $t?->accent_color;
        $this->is_active = $t?->is_active ?? true;
        $this->currentBanner = $t?->banner_image;
        $this->banner = null;
        $this->removeBanner = false;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->editingId = null;
        $this->banner = null;
    }

    public function updatedBanner(): void
    {
        $this->validate(['banner' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072']], [], ['banner' => 'gambar banner']);
    }

    public function save(): void
    {
        $hex = 'regex:/^#[0-9a-fA-F]{6}$/';
        $data = $this->validate([
            'fandom_id'       => ['required', Rule::exists('fandom', 'id')],
            'theming_name'    => ['required', 'string', 'max:80', Rule::unique('fandom_theming')->where('fandom_id', $this->fandom_id)->ignore($this->editingId ?: null)],
            'primary_color'   => ['required', $hex],
            'secondary_color' => ['required', $hex],
            'accent_color'    => ['nullable', $hex],
            'is_active'       => ['boolean'],
            'banner'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], ['regex' => ':attribute harus kode warna hex, mis. #7c3aed.'], [
            'fandom_id' => 'fandom', 'theming_name' => 'nama tema', 'primary_color' => 'warna utama',
            'secondary_color' => 'warna gradasi', 'accent_color' => 'warna aksen', 'banner' => 'gambar banner',
        ]);

        $values = [
            'fandom_id'       => (int) $data['fandom_id'],
            'theming_name'    => trim($data['theming_name']),
            'primary_color'   => strtolower($data['primary_color']),
            'secondary_color' => strtolower($data['secondary_color']),
            'accent_color'    => $data['accent_color'] ? strtolower($data['accent_color']) : null,
            'is_active'       => $this->is_active,
        ];

        $theme = $this->editingId ? FandomTheming::findOrFail($this->editingId) : new FandomTheming(['created_by' => Auth::id()]);

        if ($this->banner) {
            $this->deleteUpload($theme->banner_image);
            $values['banner_image'] = '/uploads/' . $this->banner->store('themings', 'uploads');
        } elseif ($this->removeBanner) {
            $this->deleteUpload($theme->banner_image);
            $values['banner_image'] = null;
        }

        $theme->fill($values)->save();

        $this->editingId = null;
        $this->banner = null;
        $this->dispatch('toast', message: 'Tema "' . $theme->theming_name . '" tersimpan.');
    }

    public function toggle(int $id): void
    {
        $t = FandomTheming::findOrFail($id);
        $t->update(['is_active' => ! $t->is_active]);
        $this->dispatch('toast', message: $t->is_active ? 'Tema diaktifkan.' : 'Tema dinonaktifkan — pengguna kembali ke tema otomatis/default.');
    }

    public function delete(int $id): void
    {
        $t = FandomTheming::findOrFail($id);
        $this->deleteUpload($t->banner_image);
        $t->delete(); // user.active_theming_id → NULL (FK ON DELETE SET NULL)
        $this->dispatch('toast', message: 'Tema dihapus.');
    }

    private function deleteUpload(?string $url): void
    {
        if ($url && str_starts_with($url, '/uploads/')) {
            Storage::disk('uploads')->delete(substr($url, strlen('/uploads/')));
        }
    }

    public function render()
    {
        $valid = fn ($c) => ThemePalette::valid($c);
        $primary = $valid($this->primary_color) ? $this->primary_color : ThemePalette::DEFAULT[0];
        $secondary = $valid($this->secondary_color) ? $this->secondary_color : ThemePalette::DEFAULT[1];
        $accent = $valid($this->accent_color) ? $this->accent_color : ThemePalette::DEFAULT[2];

        return view('livewire.admin.themes', [
            'fandoms' => Fandom::where('status', 'active')->orderBy('fandom_name')->get(['id', 'fandom_name']),
            'themes'  => FandomTheming::with('fandom', 'creator')->withCount('chosenBy')
                ->when($this->filterFandom, fn ($q) => $q->where('fandom_id', $this->filterFandom))
                ->get()->sortBy(fn ($t) => [$t->fandom->fandom_name, $t->theming_name])->values(),
            'preview' => [
                'brand'    => ThemePalette::scale($primary),
                'accent'   => ThemePalette::scale($secondary),
                'pop'      => ThemePalette::scale($accent),
                'contrast' => ThemePalette::contrastWithWhite($primary),
                'button'   => ThemePalette::contrastWithWhite(ThemePalette::scale($primary)[600]),
            ],
            'bannerPreview' => $this->banner ? $this->banner->temporaryUrl() : ($this->removeBanner ? null : $this->currentBanner),
        ]);
    }
}
