<?php

namespace App\Livewire\Studio;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Studio artis — daftar produk milik toko yang sedang login.
 * Artis bisa memfilter status, mengaktifkan/menonaktifkan, dan menghapus
 * (soft delete: product_status = 'deleted' agar histori pesanan tetap utuh).
 */
#[Layout('components.layouts.app')]
#[Title('Studio artis')]
class Dashboard extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: 'semua')]
    public string $status = 'semua';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updated($property): void
    {
        if (in_array($property, ['status', 'search'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function shop()
    {
        return Auth::user()->artistProfile;
    }

    #[Computed]
    public function stats(): array
    {
        $ids = Product::ownedBy($this->shop->id)->pluck('id');

        return [
            'active'   => Product::ownedBy($this->shop->id)->visible()->count(),
            'waiting'  => Product::ownedBy($this->shop->id)->where('product_status', 'active')->whereNotIn('id', Product::ownedBy($this->shop->id)->visible()->select('id'))->count(),
            'inactive' => Product::ownedBy($this->shop->id)->where('product_status', 'inactive')->count(),
            'views'    => ProductView::whereIn('product_id', $ids)->where('viewed_at', '>=', now()->subDays(30))->count(),
            'carts'    => (int) CartItem::whereIn('product_id', $ids)->sum('quantity'),
        ];
    }

    #[Computed]
    public function products()
    {
        return Product::ownedBy($this->shop->id)
            ->with(['primaryImage', 'openBatch', 'character.fandom', 'merchandiseType'])
            ->withCount(['images'])
            ->addSelect(['in_carts' => CartItem::selectRaw('COALESCE(SUM(quantity),0)')->whereColumn('product_id', 'product.id')])
            ->addSelect(['views_30d' => ProductView::selectRaw('COUNT(*)')->whereColumn('product_id', 'product.id')->where('viewed_at', '>=', now()->subDays(30))])
            ->when($this->status === 'aktif', fn ($q) => $q->visible())
            ->when($this->status === 'nonaktif', fn ($q) => $q->where('product_status', 'inactive'))
            ->when($this->status === 'po', fn ($q) => $q->where('availability_type', 'pre_order'))
            ->when($this->status === 'habis', fn ($q) => $q->where('availability_type', 'ready_stock')->where('stock', '<', 1))
            ->search($this->search)
            ->latest('created_at')
            ->paginate(10);
    }

    public function toggleStatus(int $id): void
    {
        $product = $this->own($id);
        $product->update(['product_status' => $product->product_status === 'active' ? 'inactive' : 'active']);

        $this->dispatch('toast', message: $product->product_status === 'active'
            ? 'Produk ditampilkan lagi di katalog.'
            : 'Produk disembunyikan dari katalog.');
    }

    public function delete(int $id): void
    {
        $product = $this->own($id);

        DB::transaction(function () use ($product) {
            $product->update(['product_status' => 'deleted']);
            $product->batches()->where('batch_status', 'open')->update(['batch_status' => 'cancelled']);
            CartItem::where('product_id', $product->id)->delete();
        });

        $this->dispatch('toast', message: "“{$product->product_name}” dihapus.");
    }

    private function own(int $id): Product
    {
        return Product::ownedBy($this->shop->id)->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.studio.dashboard');
    }
}
