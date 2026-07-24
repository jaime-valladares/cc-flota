<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsegurarPermiso
{
    /**
     * Verifica que el usuario autenticado tenga
     * el permiso requerido para acceder a la ruta.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $codigoPermiso
    ): Response {
        /** @var User|null $usuario */
        $usuario = $request->user();

        abort_unless($usuario, 401);

        /*
        |--------------------------------------------------------------------------
        | Superadministrador
        |--------------------------------------------------------------------------
        |
        | El superadministrador debe conservar acceso total incluso cuando sea
        | un usuario histórico cuya columna tipo_usuario todavía no esté
        | normalizada. Su rol es la autoridad definitiva para este bypass.
        |
        */

        if ($usuario->tieneRol(User::ROL_DIESEL_SUPER_ADMIN)) {
            return $next($request);
        }

        abort_unless(
            $usuario->tieneContextoEmpresaValido(),
            403,
            'El usuario no tiene un contexto empresarial válido.'
        );

        abort_unless(
            $usuario->tienePermiso($codigoPermiso),
            403,
            'No tiene autorización para acceder a esta función.'
        );

        return $next($request);
    }
}