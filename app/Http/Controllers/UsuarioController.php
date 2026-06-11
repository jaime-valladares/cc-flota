<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    /**
     * Muestra la consulta informativa de usuarios.
     */
    public function index(Request $request): View
    {
        $tipoUsuario = $request->input('tipo_usuario');
        $empresaId = $request->input('empresa_id');
        $rolId = $request->input('rol_id');

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || filled($tipoUsuario)
            || filled($empresaId)
            || filled($rolId);

        $totalUsuarios = User::count();
        $usuariosActivos = User::where('estado', 'activo')->count();
        $usuariosInactivos = User::where('estado', 'inactivo')->count();

        $empresas = Empresa::query()
            ->where('estado', 'activa')
            ->orderBy('nombre_legal')
            ->get();

        $roles = Role::query()
            ->where('estado', 'activo')
            ->orderBy('alcance')
            ->orderBy('nombre')
            ->get();

        $query = User::query()
            ->with(['empresa', 'role'])
            ->orderBy('tipo_usuario')
            ->orderBy('name')
            ->orderBy('apellido');

        if (filled($tipoUsuario)) {
            $query->where('tipo_usuario', $tipoUsuario);
        }

        if ($tipoUsuario === 'empresa' && filled($empresaId)) {
            $query->where('empresa_id', $empresaId);
        }

        if ($tipoUsuario === 'diesel_cop') {
            $query->whereNull('empresa_id');
        }

        if (filled($rolId)) {
            $query->where('rol_id', $rolId);
        }

        $usuarios = $hayFiltros
            ? $query->paginate(10)->withQueryString()
            : User::query()->whereRaw('1 = 0')->paginate(10)->withQueryString();

        return view('usuarios.index', compact(
            'usuarios',
            'tipoUsuario',
            'empresaId',
            'rolId',
            'hayFiltros',
            'totalUsuarios',
            'usuariosActivos',
            'usuariosInactivos',
            'empresas',
            'roles'
        ));
    }

    /**
     * Abre la consulta de usuarios en una nueva pestaña.
     */
    public function consultaVentana(Request $request): View
    {
        return $this->index($request);
    }
}