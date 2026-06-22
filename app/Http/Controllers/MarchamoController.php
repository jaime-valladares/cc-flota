<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Marchamo;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MarchamoController extends Controller
{
    /**
     * Consulta general de marchamos y cobertura por unidad.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $empresaId = $request->input('empresa_id');
        $unidadId = $request->input('unidad_id');
        $codigo = trim((string) $request->input('codigo_marchamo'));
        $estado = $request->input('estado');
        $origen = $request->input('origen_creacion');

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || filled($empresaId)
            || filled($unidadId)
            || filled($codigo)
            || filled($estado)
            || filled($origen);

        $mostrarDetalleMarchamos = $request->boolean('ver_detalle') && filled($unidadId);

        /*
        |--------------------------------------------------------------------------
        | Consulta de marchamos individuales
        |--------------------------------------------------------------------------
        |
        | Esta consulta alimenta la tabla detallada de marchamos. Por diseño,
        | la tabla solo se muestra cuando el usuario presiona "Ver marchamos"
        | desde una unidad específica.
        |
        */

        $marchamosQuery = Marchamo::query()
            ->with([
                'empresa',
                'unidad',
                'puntoSeguridad',
            ])
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when(is_null($user->empresa_id) && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when(filled($unidadId), function ($query) use ($unidadId) {
                $query->where('unidad_id', $unidadId);
            })
            ->when(filled($codigo), function ($query) use ($codigo) {
                $query->where('codigo_marchamo', 'like', '%' . $codigo . '%');
            })
            ->when(filled($estado), function ($query) use ($estado) {
                $query->where('estado', $estado);
            })
            ->when(filled($origen), function ($query) use ($origen) {
                $query->where('origen_creacion', $origen);
            })
            ->orderByDesc('fecha_activacion')
            ->orderByDesc('id');

        $totalMarchamosConsulta = (clone $marchamosQuery)->count();

        $marchamos = $marchamosQuery
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Cobertura por unidad
        |--------------------------------------------------------------------------
        |
        | Esta sección resume el estado operativo por unidad. Solo se mostrará
        | visualmente cuando el usuario aplique filtros.
        |
        */

        $unidadesConCobertura = Unidad::query()
            ->with(['empresa', 'licencia'])
            ->withCount([
                'puntosSeguridad as total_puntos' => function ($query) {
                    $query->where('estado', 'activo');
                },
                'puntosSeguridad as puntos_asignados' => function ($query) {
                    $query->where('estado', 'activo')
                        ->whereNotNull('marchamo_actual_id');
                },
                'marchamos as marchamos_activos' => function ($query) {
                    $query->where('estado', 'activo');
                },
                'marchamos as marchamos_historicos' => function ($query) {
                    $query->whereIn('estado', ['reemplazado', 'anulado']);
                },
            ])
            ->whereHas('licencia')
            ->whereHas('puntosSeguridad')
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when(is_null($user->empresa_id) && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when(filled($unidadId), function ($query) use ($unidadId) {
                $query->where('id', $unidadId);
            })
            ->orderBy('estado')
            ->orderBy('placa')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Métricas superiores
        |--------------------------------------------------------------------------
        |
        | Estas métricas funcionan como panorama operativo del módulo.
        |
        */

        $unidadesBaseMetricas = Unidad::query()
            ->withCount([
                'puntosSeguridad as total_puntos' => function ($query) {
                    $query->where('estado', 'activo');
                },
                'puntosSeguridad as puntos_asignados' => function ($query) {
                    $query->where('estado', 'activo')
                        ->whereNotNull('marchamo_actual_id');
                },
            ])
            ->whereHas('licencia')
            ->whereHas('puntosSeguridad')
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when(is_null($user->empresa_id) && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->get();

        $unidadesProtegidas = $unidadesBaseMetricas
            ->filter(fn ($unidad) => (int) $unidad->total_puntos > 0 && (int) $unidad->puntos_asignados === (int) $unidad->total_puntos)
            ->count();

        $unidadesConPendientes = $unidadesBaseMetricas
            ->filter(fn ($unidad) => (int) $unidad->total_puntos > 0 && (int) $unidad->puntos_asignados < (int) $unidad->total_puntos)
            ->count();

        $totalActivos = Marchamo::query()
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when(is_null($user->empresa_id) && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->where('estado', 'activo')
            ->count();

        $totalReemplazados = Marchamo::query()
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when(is_null($user->empresa_id) && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->where('estado', 'reemplazado')
            ->count();

        $totalAnulados = Marchamo::query()
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when(is_null($user->empresa_id) && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->where('estado', 'anulado')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Catálogos para filtros
        |--------------------------------------------------------------------------
        */

        $empresas = Empresa::query()
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->orderBy('nombre_legal')
            ->get();

        $unidades = Unidad::query()
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->when(is_null($user->empresa_id) && filled($empresaId), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->whereHas('licencia')
            ->whereHas('puntosSeguridad')
            ->orderBy('placa')
            ->get();

        return view('marchamos.index', [
            'marchamos' => $marchamos,
            'unidadesConCobertura' => $unidadesConCobertura,
            'empresas' => $empresas,
            'unidades' => $unidades,
            
            'empresaId' => $empresaId,
            'unidadId' => $unidadId,
            'codigo' => $codigo,
            'estado' => $estado,
            'origen' => $origen,

            'hayFiltros' => $hayFiltros,
            'mostrarDetalleMarchamos' => $mostrarDetalleMarchamos,
            'consultaEjecutada' => $consultaEjecutada,


            'totalMarchamosConsulta' => $totalMarchamosConsulta,
            'unidadesProtegidas' => $unidadesProtegidas,
            'unidadesConPendientes' => $unidadesConPendientes,
            'totalActivos' => $totalActivos,
            'totalReemplazados' => $totalReemplazados,
            'totalAnulados' => $totalAnulados,
        ]);
    }
}