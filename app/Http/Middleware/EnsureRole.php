<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembatas akses per peran. Contoh di rute: ->middleware('role:artist')
 * atau ->middleware('role:admin').
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, $roles, true), 403, 'Halaman ini bukan untuk peranmu.');

        if ($user->role === 'artist' && in_array('artist', $roles, true) && ! $user->artistProfile) {
            abort(403, 'Profil toko belum ada.');
        }

        return $next($request);
    }
}
