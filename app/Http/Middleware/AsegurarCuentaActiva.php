<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AsegurarCuentaActiva
{
    /**
     * Cierra la sesión de una cuenta desactivada por el administrador.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->activo === false) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? abort(403, 'Tu cuenta está desactivada. Contacta al administrador.')
                : redirect()->route('login')->withErrors([
                    'email' => 'Tu cuenta está desactivada. Contacta al administrador.',
                ]);
        }

        return $next($request);
    }
}
