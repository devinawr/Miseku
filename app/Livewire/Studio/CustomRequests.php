<?php

namespace App\Livewire\Studio;

use App\Models\CustomOffer;
use App\Models\CustomRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Papan permintaan custom terbuka + penawaran yang sudah dikirim artis. */
#[Layout('components.layouts.app')]
#[Title('Permintaan custom')]
class CustomRequests extends Component
{
    #[Url]
    public string $tab = 'terbuka';

    public function render()
    {
        $profile = Auth::user()->artistProfile;

        return view('livewire.studio.custom-requests', [
            'open'   => CustomRequest::with(['merchandiseType', 'buyer'])->whereIn('request_status', ['open', 'offered'])
                ->where('buyer_id', '!=', Auth::id())
                ->withCount('offers')
                ->withExists(['offers as mine' => fn ($q) => $q->where('artist_profile_id', $profile->id)->where('offer_status', 'pending')])
                ->latest('id')->get(),
            'offers' => CustomOffer::with(['request.merchandiseType', 'request.order'])->where('artist_profile_id', $profile->id)->latest('id')->get(),
        ]);
    }
}
