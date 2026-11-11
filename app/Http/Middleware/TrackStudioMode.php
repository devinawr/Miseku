<?php

namespace App\Http\Middleware;

use App\Support\StudioMode;
use Closure;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

/** Mencatat apakah artis sedang di mode Studio atau mode belanja (lihat App\Support\StudioMode). */
class TrackStudioMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($request->isMethod('GET') && ! Livewire::isLivewireRequest() && StudioMode::eligible($user)) {
            StudioMode::track($request, $user);
        }

        return $next($request);
    }
}
