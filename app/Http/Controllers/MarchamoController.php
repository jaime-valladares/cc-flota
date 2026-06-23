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
    public function index(Request $request): View
    {
        $datos = $this->obtenerDatosConsulta($request);

        return view('marchamos.index', $datos);
    }

    public function consultaVentana(Request $request): View
    {
        $datos = $this->obtenerDatosConsulta($request);

        return view('marchamos.index-ventana', $datos);
    }

    public function detalleUnidad(Request $request, Unidad $unidad): View
    {
        $datos = $this->obtenerDatosDetalleUnidad($unidad);

        return view('marchamos.detalle-unidad', $datos);
    }

    public function detalleUnidadVentana(Request $request, Unidad $unidad): View
    {
        $datos = $this->obtenerDatosDetalleUnidad($unidad);

        return view('marchamos.detalle-unidad-ventana', $datos);
    }

    private function obtenerDatosConsulta(Request $request): array
    {
        $user = Auth::user();

        $empresaId = $request->input('empresa_id');
        $unidadId = $request->input('unidad_id');

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || filled($empresaId)
            || filled($unidadId);

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

        return [
            'unidadesConCobertura' => $unidadesConCobertura,
            'empresas' => $empresas,
            'unidades' => $unidades,

            'empresaId' => $empresaId,
            'unidadId' => $unidadId,

            'hayFiltros' => $hayFiltros,
            'consultaEjecutada' => $consultaEjecutada,
        ];
    }

    private function obtenerDatosDetalleUnidad(Unidad $unidad): array
    {
        $user = Auth::user();

        if (! is_null($user->empresa_id) && (int) $unidad->empresa_id !== (int) $user->empresa_id) {
            abort(403);
        }

        $unidad->load(['empresa', 'licencia']);

        $unidad->loadCount([
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
        ]);

        $marchamos = Marchamo::query()
            ->with(['empresa', 'unidad', 'puntoSeguridad'])
            ->where('unidad_id', $unidad->id)
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->orderByDesc('fecha_activacion')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $totalMarchamos = Marchamo::query()
            ->where('unidad_id', $unidad->id)
            ->when(! is_null($user->empresa_id), function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            })
            ->count();

        return [
            'unidad' => $unidad,
            'marchamos' => $marchamos,
            'totalMarchamos' => $totalMarchamos,
        ];
    }
}