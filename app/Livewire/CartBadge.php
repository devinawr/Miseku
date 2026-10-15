<?php

namespace App\Livewire;

use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/** Ikon keranjang + jumlah item di header. Diperbarui lewat event "cart-updated". */
class CartBadge extends Component
{
    #[On('cart-updated')]
    public function refreshCount(): void
    {
        // cukup render ulang
    }

    public function render()
    {
        $count = Auth::check()
            ? (int) CartItem::where('user_id', Auth::id())->sum('quantity')
            : 0;

        return view('livewire.cart-badge', ['count' => $count]);
    }
}
