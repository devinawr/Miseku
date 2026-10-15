<?php

namespace App\Livewire\Custom;

use App\Models\CustomRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Daftar permintaan custom milik pembeli. */
#[Layout('components.layouts.app')]
#[Title('Custom merch')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.custom.index', [
            'requests' => CustomRequest::where('buyer_id', Auth::id())
                ->with(['merchandiseType', 'order'])->withCount(['offers' => fn ($q) => $q->where('offer_status', 'pending')])
                ->latest('id')->get(),
        ]);
    }
}
