<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Marchamo;
use App\Models\PuntoSeguridadUnidad;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalisisOperativoController extends Controller
{
    public function panelOperativo(Request $request)
    {
        $user = Auth::user();

        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        if (! $esUsuarioDieselCop && (! $empresaUsuario || $empresaUsuario->estado !== 'activa')) {
            abort(403, 'No se puede consultar la vista de inteligencia operativa porque la empresa está inactiva.');
        }

        $validated = $request->validate([
            'empresa_ids' => ['nullable', 'array'],
            'empresa_ids.*' => ['integer', 'exists:empresas,id'],

            'unidad_ids' => ['nullable', 'array'],
            'unidad_ids.*' => ['integer', 'exists:unidades,id'],

            'modelos_medicion' => ['nullable', 'array'],
            'modelos_medicion.*' => ['string', 'in:galones_hora,galones_kilometro,galones_viaje'],

            'total_tanques' => ['nullable', 'array'],
            'total_tanques.*' => ['integer', 'min:1', 'max:3'],
        ], [
            'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no es válida.',
            'unidad_ids.*.exists' => 'Una de las unidades seleccionadas no es válida.',
            'modelos_medicion.*.in' => 'Uno de los modelos de medición seleccionados no es válido.',
            'total_tanques.*.integer' => 'La cantidad de tanques seleccionada no es válida.',
        ]);

        $empresasSeleccionadas = collect($validated['empresa_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $unidadesSeleccionadas = collect($validated['unidad_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $modelosSeleccionados = collect($validated['modelos_medicion'] ?? [])
            ->filter()
            ->unique()
            ->values();

        $tanquesSeleccionados = collect($validated['total_tanques'] ?? [])
            ->map(fn ($valor) => (int) $valor)
            ->filter()
            ->unique()
            ->values();

        $empresasAccesiblesQuery = Empresa::query();

        if (! $esUsuarioDieselCop) {
            $empresasAccesiblesQuery->where('id', $user->empresa_id);
        }

        $empresasFiltro = (clone $empresasAccesiblesQuery)
            ->orderBy('nombre_legal')
            ->orderBy('nombre_comercial')
            ->get();

        if (! $esUsuarioDieselCop) {
            $empresasSeleccionadas = collect([(int) $user->empresa_id]);
        }

        $empresaIdsBase = $empresasSeleccionadas->isNotEmpty()
            ? $empresasSeleccionadas
            : $empresasFiltro->pluck('id')->map(fn ($id) => (int) $id)->values();

        $unidadesFiltro = Unidad::query()
            ->whereIn('empresa_id', $empresaIdsBase)
            ->orderBy('placa')
            ->get(['id', 'empresa_id', 'placa', 'modelo_medicion', 'total_tanques', 'estado']);

        $modelosFiltro = Unidad::query()
            ->whereIn('empresa_id', $empresaIdsBase)
            ->whereNotNull('modelo_medicion')
            ->select('modelo_medicion')
            ->distinct()
            ->orderBy('modelo_medicion')
            ->pluck('modelo_medicion')
            ->values();

        $tanquesFiltro = Unidad::query()
            ->whereIn('empresa_id', $empresaIdsBase)
            ->whereNotNull('total_tanques')
            ->select('total_tanques')
            ->distinct()
            ->orderBy('total_tanques')
            ->pluck('total_tanques')
            ->map(fn ($valor) => (int) $valor)
            ->values();

        $unidadesBase = Unidad::query()
            ->whereIn('empresa_id', $empresaIdsBase);

        if ($unidadesSeleccionadas->isNotEmpty()) {
            $unidadesBase->whereIn('id', $unidadesSeleccionadas);
        }

        if ($modelosSeleccionados->isNotEmpty()) {
            $unidadesBase->whereIn('modelo_medicion', $modelosSeleccionados);
        }

        if ($tanquesSeleccionados->isNotEmpty()) {
            $unidadesBase->whereIn('total_tanques', $tanquesSeleccionados);
        }

        $unidadIds = (clone $unidadesBase)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $empresaIdsResultado = (clone $unidadesBase)
            ->select('empresa_id')
            ->distinct()
            ->pluck('empresa_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($empresaIdsResultado->isEmpty()) {
            $empresaIdsResultado = $empresaIdsBase;
        }

        $empresasResultado = Empresa::query()
            ->whereIn('id', $empresaIdsResultado)
            ->orderBy('nombre_legal')
            ->orderBy('nombre_comercial')
            ->get();

        $conteoPuntosPorUnidad = $this->obtenerConteoPuntosPorUnidad($unidadIds);
        $marchamosActivosPorUnidad = $this->obtenerMarchamosActivosPorUnidad($unidadIds);

        $cobertura = $this->calcularCoberturaGlobal($conteoPuntosPorUnidad);

        $unidadesSinLicenciaActiva = (clone $unidadesBase)
            ->whereDoesntHave('licencia', function ($query) {
                $query->where('estado', 'activa');
            })
            ->count();

        $kpis = [
            'empresas_activas' => Empresa::query()
                ->whereIn('id', $empresaIdsResultado)
                ->where('estado', 'activa')
                ->count(),

            'empresas_inactivas' => Empresa::query()
                ->whereIn('id', $empresaIdsResultado)
                ->where('estado', 'inactiva')
                ->count(),

            'unidades_activas' => (clone $unidadesBase)
                ->where('estado', 'activa')
                ->count(),

            'unidades_registradas' => (clone $unidadesBase)
                ->where('estado', 'registrada')
                ->count(),

            'unidades_inactivas' => (clone $unidadesBase)
                ->where('estado', 'inactiva')
                ->count(),

            'unidades_cobertura_completa' => $cobertura['completas'],
            'unidades_cobertura_incompleta' => $cobertura['incompletas'],
            'unidades_sin_licencia_activa' => $unidadesSinLicenciaActiva,

            'marchamos_activos' => Marchamo::query()
                ->whereIn('unidad_id', $unidadIds)
                ->where('estado', 'activo')
                ->where('activo_actual', 1)
                ->count(),

            'marchamos_reemplazados' => Marchamo::query()
                ->whereIn('unidad_id', $unidadIds)
                ->where('estado', 'reemplazado')
                ->count(),
        ];

        $saludOperativa = $this->prepararSaludOperativa($empresasResultado, $kpis);





        $unidadesAnaliticasColeccion = $this->prepararUnidadesAnaliticas(
            $unidadIds,
            $conteoPuntosPorUnidad,
            $marchamosActivosPorUnidad
        );

        $paginaUnidades = max((int) $request->input('unidad_page', 1), 1);
        $porPaginaUnidades = 20;

        $unidadesAnaliticas = new LengthAwarePaginator(
            $unidadesAnaliticasColeccion->forPage($paginaUnidades, $porPaginaUnidades)->values(),
            $unidadesAnaliticasColeccion->count(),
            $porPaginaUnidades,
            $paginaUnidades,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'unidad_page',
            ]
        );

        $resumenConsolidadoColeccion = $this->prepararResumenConsolidado(
            $empresasResultado,
            $unidadesBase,
            $conteoPuntosPorUnidad
        );

        $paginaEmpresas = max((int) $request->input('empresa_page', 1), 1);
        $porPaginaEmpresas = 10;

        $resumenConsolidado = new LengthAwarePaginator(
            $resumenConsolidadoColeccion->forPage($paginaEmpresas, $porPaginaEmpresas)->values(),
            $resumenConsolidadoColeccion->count(),
            $porPaginaEmpresas,
            $paginaEmpresas,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'empresa_page',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad temporal
        |--------------------------------------------------------------------------
        | Estas variables conservan compatibilidad con el Blade actual mientras
        | reemplazamos la vista en el siguiente paso.
        */

        $empresaId = $empresasSeleccionadas->first();
        $estadoEmpresa = null;
        $estadoUnidad = null;
        $empresasSelector = $empresasFiltro;
        $alertas = $saludOperativa;
        $resumenEmpresas = $resumenConsolidado;
        $unidadesAtencion = $unidadesAnaliticas;

        return view('analisis.panel-operativo', [
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,

            'empresasFiltro' => $empresasFiltro,
            'unidadesFiltro' => $unidadesFiltro,
            'modelosFiltro' => $modelosFiltro,
            'tanquesFiltro' => $tanquesFiltro,

            'empresasSeleccionadas' => $empresasSeleccionadas,
            'unidadesSeleccionadas' => $unidadesSeleccionadas,
            'modelosSeleccionados' => $modelosSeleccionados,
            'tanquesSeleccionados' => $tanquesSeleccionados,

            'kpis' => $kpis,
            'saludOperativa' => $saludOperativa,
            'unidadesAnaliticas' => $unidadesAnaliticas,
            'resumenConsolidado' => $resumenConsolidado,

            'empresasSelector' => $empresasSelector,
            'empresaId' => $empresaId,
            'estadoEmpresa' => $estadoEmpresa,
            'estadoUnidad' => $estadoUnidad,
            'alertas' => $alertas,
            'resumenEmpresas' => $resumenEmpresas,
            'unidadesAtencion' => $unidadesAtencion,
        ]);
    }

    private function obtenerConteoPuntosPorUnidad($unidadIds)
    {
        if ($unidadIds->isEmpty()) {
            return collect();
        }

        return PuntoSeguridadUnidad::query()
            ->select([
                'unidad_id',
                DB::raw('COUNT(*) as total_requeridos'),
                DB::raw('SUM(CASE WHEN marchamo_actual_id IS NOT NULL THEN 1 ELSE 0 END) as total_asignados'),
            ])
            ->whereIn('unidad_id', $unidadIds)
            ->where('estado', 'activo')
            ->where('requiere_marchamo', true)
            ->groupBy('unidad_id')
            ->get()
            ->keyBy('unidad_id');
    }

    private function obtenerMarchamosActivosPorUnidad($unidadIds)
    {
        if ($unidadIds->isEmpty()) {
            return collect();
        }

        return Marchamo::query()
            ->select([
                'unidad_id',
                DB::raw('COUNT(*) as total_activos'),
            ])
            ->whereIn('unidad_id', $unidadIds)
            ->where('estado', 'activo')
            ->where('activo_actual', 1)
            ->groupBy('unidad_id')
            ->get()
            ->keyBy('unidad_id');
    }

    private function calcularCoberturaGlobal($conteoPuntosPorUnidad): array
    {
        $completas = 0;
        $incompletas = 0;

        foreach ($conteoPuntosPorUnidad as $conteo) {
            $requeridos = (int) $conteo->total_requeridos;
            $asignados = (int) $conteo->total_asignados;

            if ($requeridos <= 0) {
                continue;
            }

            if ($asignados >= $requeridos) {
                $completas++;
            } else {
                $incompletas++;
            }
        }

        return [
            'completas' => $completas,
            'incompletas' => $incompletas,
        ];
    }

    private function prepararSaludOperativa($empresas, array $kpis): array
    {
        $empresasActivasSinUnidades = $empresas
            ->filter(function ($empresa) {
                return $empresa->estado === 'activa'
                    && Unidad::query()->where('empresa_id', $empresa->id)->count() === 0;
            })
            ->count();

        $empresasInactivasConUnidades = $empresas
            ->filter(function ($empresa) {
                return $empresa->estado === 'inactiva'
                    && Unidad::query()->where('empresa_id', $empresa->id)->count() > 0;
            })
            ->count();

        return [
            [
                'titulo' => 'Cobertura incompleta',
                'valor' => $kpis['unidades_cobertura_incompleta'],
                'detalle' => 'Unidades con puntos de seguridad pendientes de marchamo.',
                'nivel' => $kpis['unidades_cobertura_incompleta'] > 0 ? 'alto' : 'ok',
                'estado' => $kpis['unidades_cobertura_incompleta'] > 0 ? 'Atención' : 'Normal',
            ],
            [
                'titulo' => 'Unidades registradas',
                'valor' => $kpis['unidades_registradas'],
                'detalle' => 'Unidades pendientes de completar su proceso inicial.',
                'nivel' => $kpis['unidades_registradas'] > 0 ? 'medio' : 'ok',
                'estado' => $kpis['unidades_registradas'] > 0 ? 'Seguimiento' : 'Normal',
            ],
            [
                'titulo' => 'Sin licencia activa',
                'valor' => $kpis['unidades_sin_licencia_activa'],
                'detalle' => 'Unidades que no tienen una licencia activa asociada.',
                'nivel' => $kpis['unidades_sin_licencia_activa'] > 0 ? 'medio' : 'ok',
                'estado' => $kpis['unidades_sin_licencia_activa'] > 0 ? 'Revisión' : 'Normal',
            ],
            [
                'titulo' => 'Empresas sin unidades',
                'valor' => $empresasActivasSinUnidades,
                'detalle' => 'Empresas activas que todavía no tienen unidades registradas.',
                'nivel' => $empresasActivasSinUnidades > 0 ? 'bajo' : 'ok',
                'estado' => $empresasActivasSinUnidades > 0 ? 'Informativo' : 'Normal',
            ],
            [
                'titulo' => 'Historial inactivo',
                'valor' => $empresasInactivasConUnidades,
                'detalle' => 'Empresas inactivas que conservan unidades históricas.',
                'nivel' => $empresasInactivasConUnidades > 0 ? 'info' : 'ok',
                'estado' => $empresasInactivasConUnidades > 0 ? 'Histórico' : 'Normal',
            ],
        ];
    }

    private function prepararUnidadesAnaliticas($unidadIds, $conteoPuntosPorUnidad, $marchamosActivosPorUnidad)
    {
        if ($unidadIds->isEmpty()) {
            return collect();
        }

        $unidades = Unidad::query()
            ->with(['empresa', 'licencia'])
            ->whereIn('id', $unidadIds)
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();

        return $unidades
            ->map(function ($unidad) use ($conteoPuntosPorUnidad, $marchamosActivosPorUnidad) {
                $conteo = $conteoPuntosPorUnidad->get($unidad->id);

                $totalPuntos = $conteo ? (int) $conteo->total_requeridos : 0;
                $puntosAsignados = $conteo ? (int) $conteo->total_asignados : 0;

                $porcentajeCobertura = $totalPuntos > 0
                    ? round(($puntosAsignados / $totalPuntos) * 100, 2)
                    : 0;

                $marchamosActivos = $marchamosActivosPorUnidad->get($unidad->id)
                    ? (int) $marchamosActivosPorUnidad->get($unidad->id)->total_activos
                    : 0;

                $licenciaTexto = $this->formatearLicencia($unidad);
                $situacion = $this->definirSituacionUnidad($unidad, $totalPuntos, $puntosAsignados);
                $accionSugerida = $this->definirAccionSugerida($situacion);

                return [
                    'empresa' => $unidad->empresa,
                    'unidad' => $unidad,
                    'modelo_medicion' => $this->formatearModeloMedicion($unidad->modelo_medicion),
                    'total_tanques' => (int) $unidad->total_tanques,
                    'licencia' => $unidad->licencia,
                    'licencia_texto' => $licenciaTexto,
                    'marchamos_activos' => $marchamosActivos,
                    'total_puntos' => $totalPuntos,
                    'puntos_asignados' => $puntosAsignados,
                    'porcentaje_cobertura' => $porcentajeCobertura,
                    'situacion' => $situacion,
                    'accion_sugerida' => $accionSugerida,
                    'orden_situacion' => $this->ordenSituacion($situacion),
                ];
            })
            ->sortBy([
                ['orden_situacion', 'asc'],
                ['empresa.nombre_legal', 'asc'],
                ['unidad.placa', 'asc'],
            ])
            ->values();
    }

    private function prepararResumenConsolidado($empresas, $unidadesBase, $conteoPuntosPorUnidad)
    {
        return $empresas->map(function ($empresa) use ($unidadesBase, $conteoPuntosPorUnidad) {
            $unidades = (clone $unidadesBase)
                ->where('empresa_id', $empresa->id)
                ->get(['id', 'estado']);

            $unidadesCompletas = 0;
            $unidadesIncompletas = 0;

            foreach ($unidades as $unidad) {
                $conteo = $conteoPuntosPorUnidad->get($unidad->id);

                if (! $conteo) {
                    continue;
                }

                $requeridos = (int) $conteo->total_requeridos;
                $asignados = (int) $conteo->total_asignados;

                if ($requeridos <= 0) {
                    continue;
                }

                if ($asignados >= $requeridos) {
                    $unidadesCompletas++;
                } else {
                    $unidadesIncompletas++;
                }
            }

            return [
                'empresa' => $empresa,
                'total_unidades' => $unidades->count(),
                'unidades_activas' => $unidades->where('estado', 'activa')->count(),
                'unidades_registradas' => $unidades->where('estado', 'registrada')->count(),
                'unidades_inactivas' => $unidades->where('estado', 'inactiva')->count(),
                'cobertura_completa' => $unidadesCompletas,
                'cobertura_incompleta' => $unidadesIncompletas,
                'marchamos_activos' => Marchamo::query()
                    ->where('empresa_id', $empresa->id)
                    ->whereIn('unidad_id', $unidades->pluck('id'))
                    ->where('estado', 'activo')
                    ->where('activo_actual', 1)
                    ->count(),
                'marchamos_reemplazados' => Marchamo::query()
                    ->where('empresa_id', $empresa->id)
                    ->whereIn('unidad_id', $unidades->pluck('id'))
                    ->where('estado', 'reemplazado')
                    ->count(),
            ];
        });
    }

    private function formatearLicencia(Unidad $unidad): string
    {
        if (! $unidad->licencia) {
            return 'Sin licencia';
        }

        $estado = $unidad->licencia->estado === 'activa'
            ? 'Activa'
            : 'Inactiva';

        if ($unidad->licencia->fecha_vencimiento) {
            $verbo = $unidad->licencia->estado === 'activa'
                ? 'vence'
                : 'venció';

            return $estado . ' — ' . $verbo . ' ' . $unidad->licencia->fecha_vencimiento->format('Y-m-d');
        }

        if ($unidad->licencia->periodo_vigencia_meses) {
            return $estado . ' — ' . (int) $unidad->licencia->periodo_vigencia_meses . ' meses';
        }

        return $estado . ' — sin vencimiento definido';
    }

    private function definirSituacionUnidad(Unidad $unidad, int $totalPuntos, int $puntosAsignados): string
    {
        $tieneLicenciaActiva = $unidad->licencia && $unidad->licencia->estado === 'activa';

        if ($unidad->estado === 'registrada') {
            return 'Pendiente de asignación inicial';
        }

        if ($unidad->estado === 'inactiva') {
            return 'Unidad inactiva';
        }

        if (! $tieneLicenciaActiva) {
            return 'Sin licencia activa';
        }

        if ($totalPuntos > 0 && $puntosAsignados < $totalPuntos) {
            return 'Cobertura incompleta';
        }

        return 'Operación normal';
    }

    private function definirAccionSugerida(string $situacion): string
    {
        return match ($situacion) {
            'Pendiente de asignación inicial' => 'Completar asignación inicial',
            'Sin licencia activa' => 'Revisar o activar licencia',
            'Cobertura incompleta' => 'Revisar puntos de seguridad',
            'Unidad inactiva' => 'Consultar historial',
            default => 'Sin acción requerida',
        };
    }

    private function ordenSituacion(string $situacion): int
    {
        return match ($situacion) {
            'Sin licencia activa' => 1,
            'Cobertura incompleta' => 2,
            'Pendiente de asignación inicial' => 3,
            'Unidad inactiva' => 4,
            default => 5,
        };
    }

    private function formatearModeloMedicion(?string $modelo): string
    {
        return match ($modelo) {
            'galones_hora' => 'Galones por hora',
            'galones_kilometro' => 'Galones por kilómetro',
            'galones_viaje' => 'Galones por viaje',
            default => 'No definido',
        };
    }
}