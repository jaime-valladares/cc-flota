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
        $esUsuarioDieselCop = is_null($user->empresa_id);

        $busquedaEmpresa = trim((string) $request->input('busqueda_empresa', ''));
        $busquedaPlaca = trim((string) $request->input('busqueda_placa', ''));

        /*
         * Compatibilidad:
         * - Nueva consulta: empresa_ids[] y placas[].
         * - Consulta anterior: empresa_id y unidad_id.
         */
        $empresaIds = collect($request->input('empresa_ids', []))
            ->when(filled($request->input('empresa_id')), function ($collection) use ($request) {
                return $collection->push($request->input('empresa_id'));
            })
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $placas = collect($request->input('placas', []))
            ->filter(fn ($placa) => filled($placa))
            ->map(fn ($placa) => trim((string) $placa))
            ->unique()
            ->values()
            ->all();

        $unidadId = $request->input('unidad_id');

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $empresaId = $empresaIds[0] ?? null;
        $placa = $placas[0] ?? null;

        $consultaEjecutada = $request->boolean('consultar');

        $hayFiltros = $consultaEjecutada
            || filled($busquedaEmpresa)
            || filled($busquedaPlaca)
            || count($empresaIds) > 0
            || count($placas) > 0
            || filled($unidadId);

        $empresas = Empresa::query()
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('id', $user->empresa_id);
            })
            ->orderBy('nombre_comercial')
            ->orderBy('nombre_legal')
            ->get();

        $baseUnidadesQuery = Unidad::query()
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
            ->when(! $esUsuarioDieselCop, function ($query) use ($user) {
                $query->where('empresa_id', $user->empresa_id);
            });

        $placasSelector = (clone $baseUnidadesQuery)
            ->orderBy('placa')
            ->pluck('placa')
            ->filter()
            ->unique()
            ->values();

        $unidades = (clone $baseUnidadesQuery)
            ->orderBy('placa')
            ->get();
            
        $unidadesConCobertura = (clone $baseUnidadesQuery)
            ->when($hayFiltros && filled($busquedaEmpresa), function ($query) use ($busquedaEmpresa) {
                $query->whereHas('empresa', function ($empresaQuery) use ($busquedaEmpresa) {
                    $empresaQuery
                        ->where('nombre_legal', 'like', '%' . $busquedaEmpresa . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $busquedaEmpresa . '%');
                });
            })
            ->when($hayFiltros && count($empresaIds) > 0, function ($query) use ($empresaIds) {
                $query->whereIn('empresa_id', $empresaIds);
            })
            ->when($hayFiltros && filled($busquedaPlaca), function ($query) use ($busquedaPlaca) {
                $query->where('placa', 'like', '%' . $busquedaPlaca . '%');
            })
            ->when($hayFiltros && count($placas) > 0, function ($query) use ($placas) {
                $query->whereIn('placa', $placas);
            })
            ->when($hayFiltros && filled($unidadId), function ($query) use ($unidadId) {
                $query->where('id', $unidadId);
            })
            ->when(! $hayFiltros, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('estado')
            ->orderBy('placa')
            ->get();

        return [
            'unidadesConCobertura' => $unidadesConCobertura,
            'empresas' => $empresas,
            'unidades' => $unidades,
            'placasSelector' => $placasSelector,

            'busquedaEmpresa' => $busquedaEmpresa,
            'busquedaPlaca' => $busquedaPlaca,

            'empresaIds' => $empresaIds,
            'placas' => $placas,

            /*
             * Variables simples para compatibilidad temporal.
             */
            'empresaId' => $empresaId,
            'unidadId' => $unidadId,
            'placa' => $placa,

            'hayFiltros' => $hayFiltros,
            'consultaEjecutada' => $consultaEjecutada,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
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