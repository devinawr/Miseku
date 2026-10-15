<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Moderasi produk: sembunyikan / tayangkan kembali produk yang melanggar. */
#[Layout('components.layouts.app')]
#[Title('Moderasi produk')]
class Products extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $p = Product::where('product_status', '!=', 'deleted')->findOrFail($id);
        $p->update(['product_status' => $p->product_status === 'active' ? 'inactive' : 'active']);
        $this->dispatch('toast', message: $p->product_status === 'active' ? 'Produk ditayangkan.' : 'Produk disembunyikan dari katalog.');
    }

    public function render()
    {
        return view('livewire.admin.products', [
            'products' => Product::with(['primaryImage', 'artistProfile', 'character.fandom', 'merchandiseType'])
                ->when($this->q, fn ($w) => $w->where('product_name', 'like', "%{$this->q}%"))
                ->when($this->status, fn ($w) => $w->where('product_status', $this->status))
                ->latest('id')->paginate(20),
        ]);
    }
}
