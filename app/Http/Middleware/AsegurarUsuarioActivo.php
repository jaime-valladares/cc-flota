<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AsegurarUsuarioActivo
{
    /**
     * Impide que una cuenta inactiva conserve acceso mediante
     * una sesión iniciada previamente.
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->route('login');
        }

        if ($usuario->estado === 'activo') {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Su cuenta no se encuentra habilitada para acceder al sistema.');
    }
}