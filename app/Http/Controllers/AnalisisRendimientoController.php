<?php

namespace App\Http\Controllers;

use App\Models\Abastecimiento;
use App\Models\Empresa;
use App\Models\Motorista;
use App\Models\Unidad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalisisRendimientoController extends Controller
{
    public function index(Request $request): View
    {
        return view(
            'analisis-rendimientos.index',
            $this->prepararAnalisis($request)
        );
    }

    public function indexVentana(Request $request): View
    {
        return view(
            'analisis-rendimientos.index-ventana',
            $this->prepararAnalisis($request)
        );
    }

    private function prepararAnalisis(Request $request): array
    {
        $user = Auth::user();
        $esUsuarioDieselCop = is_null($user->empresa_id);
        $empresaUsuario = $esUsuarioDieselCop
            ? null
            : Empresa::find($user->empresa_id);

        $validated = $request->validate(
            [
                'empresa_ids' => ['nullable', 'array'],
                'empresa_ids.*' => ['integer', 'distinct', 'exists:empresas,id'],
                'unidad_ids' => ['nullable', 'array'],
                'unidad_ids.*' => ['integer', 'distinct', 'exists:unidades,id'],
                'motorista_ids' => ['nullable', 'array'],
                'motorista_ids.*' => ['integer', 'distinct', 'exists:motoristas,id'],
                'modelos_medicion' => ['nullable', 'array'],
                'modelos_medicion.*' => [
                    'string',
                    'distinct',
                    Rule::in([
                        Abastecimiento::MODELO_GALONES_KILOMETRO,
                        Abastecimiento::MODELO_GALONES_HORA,
                        Abastecimiento::MODELO_GALONES_VIAJE,
                    ]),
                ],
                'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
                'fecha_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
                'busqueda' => ['nullable', 'string', 'max:150'],
                'empresa_id' => ['nullable', 'integer', 'exists:empresas,id'],
                'unidad_id' => ['nullable', 'integer', 'exists:unidades,id'],
                'motorista_id' => ['nullable', 'integer', 'exists:motoristas,id'],
                'modelo_medicion' => [
                    'nullable',
                    Rule::in([
                        Abastecimiento::MODELO_GALONES_KILOMETRO,
                        Abastecimiento::MODELO_GALONES_HORA,
                        Abastecimiento::MODELO_GALONES_VIAJE,
                    ]),
                ],
            ],
            [
                'empresa_ids.array' => 'La selección de empresas no es válida.',
                'empresa_ids.*.exists' => 'Una de las empresas seleccionadas no existe.',
                'unidad_ids.array' => 'La selección de unidades no es válida.',
                'unidad_ids.*.exists' => 'Una de las unidades seleccionadas no existe.',
                'motorista_ids.array' => 'La selección de motoristas no es válida.',
                'motorista_ids.*.exists' => 'Uno de los motoristas seleccionados no existe.',
                'modelos_medicion.array' => 'La selección de modelos no es válida.',
                'modelos_medicion.*.in' => 'Uno de los modelos seleccionados no es válido.',
                'fecha_hasta.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
            ]
        );

        $empresaIds = $this->normalizarIdsSeleccionados(
            $validated['empresa_ids'] ?? [],
            $validated['empresa_id'] ?? null
        );

        $unidadIds = $this->normalizarIdsSeleccionados(
            $validated['unidad_ids'] ?? [],
            $validated['unidad_id'] ?? null
        );

        $motoristaIds = $this->normalizarIdsSeleccionados(
            $validated['motorista_ids'] ?? [],
            $validated['motorista_id'] ?? null
        );

        $modelosMedicion = $this->normalizarModelosSeleccionados(
            $validated['modelos_medicion'] ?? [],
            $validated['modelo_medicion'] ?? null
        );

        if (! $esUsuarioDieselCop) {
            $empresaIds = [(int) $user->empresa_id];
        }

        $fechaDesde = $validated['fecha_desde'] ?? null;
        $fechaHasta = $validated['fecha_hasta'] ?? null;
        $busqueda = trim((string) ($validated['busqueda'] ?? ''));

        $hayFiltros = ! $esUsuarioDieselCop
            || $request->hasAny([
                'empresa_ids',
                'empresa_id',
                'unidad_ids',
                'unidad_id',
                'motorista_ids',
                'motorista_id',
                'modelos_medicion',
                'modelo_medicion',
                'fecha_desde',
                'fecha_hasta',
                'busqueda',
                'consultar',
            ]);

        $consultaBase = Abastecimiento::query()->registrados();

        if (! $esUsuarioDieselCop) {
            $consultaBase->where('empresa_id', $user->empresa_id);
        }

        if (! $hayFiltros) {
            $consultaBase->whereRaw('1 = 0');
        } else {
            $this->aplicarFiltrosAnaliticos(
                $consultaBase,
                $empresaIds,
                $unidadIds,
                $motoristaIds,
                $modelosMedicion,
                $fechaDesde,
                $fechaHasta,
                $busqueda
            );
        }

        $queryResultados = clone $consultaBase;

        $queryResultados->with([
            'empresa',
            'unidad',
            'motorista',
            'gasolineraInterna',
            'gasolineraExterna',
            'rutas' => fn ($query) => $query->orderBy('orden'),
        ]);

        $abastecimientos = $queryResultados
            ->orderByDesc('fecha_hora_abastecimiento')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $abastecimientos->getCollection()->transform(
            fn (Abastecimiento $abastecimiento): Abastecimiento =>
                $this->prepararFilaAnalitica($abastecimiento)
        );

        $empresasSelector = $this->obtenerEmpresasSelector(
            $esUsuarioDieselCop,
            $empresaUsuario
        );

        $unidadesSelector = $this->obtenerUnidadesSelector(
            $esUsuarioDieselCop,
            $user->empresa_id,
            $empresaIds
        );

        $motoristasSelector = $this->obtenerMotoristasSelector(
            $esUsuarioDieselCop,
            $user->empresa_id,
            $empresaIds
        );

        $unidadIds = $this->filtrarIdsPermitidos(
            $unidadIds,
            $unidadesSelector->pluck('id')
        );

        $motoristaIds = $this->filtrarIdsPermitidos(
            $motoristaIds,
            $motoristasSelector->pluck('id')
        );

        $resumen = $this->obtenerResumenAnalitico(clone $consultaBase);

        return [
            'abastecimientos' => $abastecimientos,
            'empresasSelector' => $empresasSelector,
            'unidadesSelector' => $unidadesSelector,
            'motoristasSelector' => $motoristasSelector,
            'empresaIds' => $empresaIds,
            'unidadIds' => $unidadIds,
            'motoristaIds' => $motoristaIds,
            'modelosMedicion' => $modelosMedicion,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'busqueda' => $busqueda,
            'hayFiltros' => $hayFiltros,
            'esUsuarioDieselCop' => $esUsuarioDieselCop,
            'empresaUsuario' => $empresaUsuario,
            'resumen' => $resumen,
            'tipoResumen' => $this->determinarTipoResumen($modelosMedicion),
            'opcionesModelos' => [
                Abastecimiento::MODELO_GALONES_VIAJE => 'Galones por viaje',
                Abastecimiento::MODELO_GALONES_KILOMETRO => 'Kilómetros por galón',
                Abastecimiento::MODELO_GALONES_HORA => 'Galones por hora',
            ],
        ];
    }

    private function aplicarFiltrosAnaliticos(
        Builder $query,
        array $empresaIds,
        array $unidadIds,
        array $motoristaIds,
        array $modelosMedicion,
        ?string $fechaDesde,
        ?string $fechaHasta,
        string $busqueda
    ): void {
        if ($empresaIds !== []) {
            $query->whereIn('empresa_id', $empresaIds);
        }

        if ($unidadIds !== []) {
            $query->whereIn('unidad_id', $unidadIds);
        }

        if ($motoristaIds !== []) {
            $query->whereIn('motorista_id', $motoristaIds);
        }

        if ($modelosMedicion !== []) {
            $query->whereIn('modelo_medicion', $modelosMedicion);
        }

        if ($fechaDesde) {
            $query->where(
                'fecha_hora_abastecimiento',
                '>=',
                Carbon::createFromFormat('Y-m-d', $fechaDesde)->startOfDay()
            );
        }

        if ($fechaHasta) {
            $query->where(
                'fecha_hora_abastecimiento',
                '<',
                Carbon::createFromFormat('Y-m-d', $fechaHasta)
                    ->addDay()
                    ->startOfDay()
            );
        }

        if ($busqueda !== '') {
            $query->where(function (Builder $busquedaQuery) use ($busqueda): void {
                $termino = '%' . $busqueda . '%';

                $busquedaQuery
                    ->where('empresa_nombre_snapshot', 'like', $termino)
                    ->orWhere('unidad_placa_snapshot', 'like', $termino)
                    ->orWhere('unidad_marca_snapshot', 'like', $termino)
                    ->orWhere('unidad_modelo_snapshot', 'like', $termino)
                    ->orWhere('motorista_nombre_snapshot', 'like', $termino)
                    ->orWhere('motorista_licencia_snapshot', 'like', $termino)
                    ->orWhere('origen_nombre_snapshot', 'like', $termino)
                    ->orWhere('modelo_medicion', 'like', $termino)
                    ->orWhereHas('rutas', function (Builder $rutaQuery) use ($termino): void {
                        $rutaQuery
                            ->where('ruta_nombre_snapshot', 'like', $termino)
                            ->orWhere('punto_origen_nombre_snapshot', 'like', $termino)
                            ->orWhere('punto_destino_nombre_snapshot', 'like', $termino);
                    });

                if (ctype_digit($busqueda)) {
                    $busquedaQuery->orWhere('id', (int) $busqueda);
                }
            });
        }
    }

    private function prepararFilaAnalitica(
        Abastecimiento $abastecimiento
    ): Abastecimiento {
        $esLineaBase = is_null($abastecimiento->abastecimiento_anterior_id);

        $abastecimiento->setAttribute(
            'es_linea_base_analitica',
            $esLineaBase
        );

        $abastecimiento->setAttribute(
            'empresa_texto_analitico',
            $abastecimiento->empresa_nombre_snapshot
                ?: ($abastecimiento->empresa
                    ? ($abastecimiento->empresa->nombre_comercial
                        ?: $abastecimiento->empresa->nombre_legal)
                    : 'No disponible')
        );

        $abastecimiento->setAttribute(
            'unidad_texto_analitico',
            $abastecimiento->unidad_placa_snapshot
                ?: ($abastecimiento->unidad
                    ? $abastecimiento->unidad->placa
                    : 'No disponible')
        );

        $abastecimiento->setAttribute(
            'motorista_texto_analitico',
            $abastecimiento->motorista_nombre_snapshot
                ?: ($abastecimiento->motorista
                    ? trim(
                        $abastecimiento->motorista->nombres
                        . ' '
                        . $abastecimiento->motorista->apellidos
                    )
                    : 'No disponible')
        );

        $abastecimiento->setAttribute(
            'origen_texto_analitico',
            $abastecimiento->origen_nombre_snapshot ?: 'No disponible'
        );

        $abastecimiento->setAttribute(
            'recorrido_resumen_analitico',
            $abastecimiento->rutas
                ->map(fn ($ruta): string => $ruta->recorrido_texto)
                ->filter()
                ->implode(' | ')
        );

        $variacionGalones = null;

        if (
            ! $esLineaBase
            && $abastecimiento->modelo_medicion === Abastecimiento::MODELO_GALONES_VIAJE
            && (float) $abastecimiento->galones_teoricos > 0
            && ! is_null($abastecimiento->combustible_consumido_ciclo)
        ) {
            $variacionGalones = (
                (
                    (float) $abastecimiento->combustible_consumido_ciclo
                    - (float) $abastecimiento->galones_teoricos
                )
                / (float) $abastecimiento->galones_teoricos
            ) * 100;
        }

        $abastecimiento->setAttribute(
            'variacion_galones_porcentaje',
            is_null($variacionGalones)
                ? null
                : round($variacionGalones, 2)
        );

        $abastecimiento->setAttribute(
            'estado_analitico',
            $this->determinarEstadoAnalitico(
                $abastecimiento,
                $esLineaBase
            )
        );

        return $abastecimiento;
    }

    private function determinarEstadoAnalitico(
        Abastecimiento $abastecimiento,
        bool $esLineaBase
    ): string {
        if ($esLineaBase) {
            return 'Línea base';
        }

        if (
            is_null($abastecimiento->combustible_consumido_ciclo)
            || (float) $abastecimiento->combustible_consumido_ciclo <= 0
        ) {
            return 'Información incompleta';
        }

        if ($abastecimiento->modelo_medicion !== Abastecimiento::MODELO_GALONES_VIAJE) {
            return 'Resultado real';
        }

        if (
            is_null($abastecimiento->galones_teoricos)
            || (float) $abastecimiento->galones_teoricos <= 0
        ) {
            return 'Sin base teórica';
        }

        $diferencia = (float) $abastecimiento->combustible_consumido_ciclo
            - (float) $abastecimiento->galones_teoricos;

        if (abs($diferencia) < 0.01) {
            return 'Dentro de lo esperado';
        }

        return $diferencia > 0
            ? 'Consumo superior a lo esperado'
            : 'Consumo inferior a lo esperado';
    }

    private function obtenerResumenAnalitico(Builder $base): array
    {
        $lineasBase = (clone $base)
            ->whereNull('abastecimiento_anterior_id')
            ->count();

        $ciclos = (clone $base)
            ->whereNotNull('abastecimiento_anterior_id');

        $comun = [
            'registros' => (clone $base)->count(),
            'ciclos' => (clone $ciclos)->count(),
            'lineas_base' => $lineasBase,
            'unidades' => (clone $base)->distinct()->count('unidad_id'),
            'motoristas' => (clone $base)->distinct()->count('motorista_id'),
            'galones_consumidos' => (float) ((clone $ciclos)
                ->sum('combustible_consumido_ciclo') ?? 0),
            'ciclos_incompletos' => (clone $ciclos)
                ->where(function (Builder $query): void {
                    $query
                        ->whereNull('combustible_consumido_ciclo')
                        ->orWhere('combustible_consumido_ciclo', '<=', 0);
                })
                ->count(),
        ];

        $viajes = (clone $ciclos)
            ->where('modelo_medicion', Abastecimiento::MODELO_GALONES_VIAJE);

        $kilometros = (clone $ciclos)
            ->where('modelo_medicion', Abastecimiento::MODELO_GALONES_KILOMETRO);

        $horas = (clone $ciclos)
            ->where('modelo_medicion', Abastecimiento::MODELO_GALONES_HORA);

        return [
            'comun' => $comun,
            'viaje' => [
                'ciclos' => (clone $viajes)->count(),
                'kilometros_teoricos' => (float) ((clone $viajes)->sum('kilometros_teoricos') ?? 0),
                'galones_teoricos' => (float) ((clone $viajes)->sum('galones_teoricos') ?? 0),
                'galones_reales' => (float) ((clone $viajes)->sum('combustible_consumido_ciclo') ?? 0),
                'diferencia_total' => (float) ((clone $viajes)
                    ->selectRaw(
                        'SUM(COALESCE(combustible_consumido_ciclo, 0) '
                        . '- COALESCE(galones_teoricos, 0)) as diferencia'
                    )
                    ->value('diferencia') ?? 0),
                'variacion_promedio' => (float) ((clone $viajes)
                    ->where('galones_teoricos', '>', 0)
                    ->selectRaw(
                        'AVG((combustible_consumido_ciclo - galones_teoricos) '
                        . '/ galones_teoricos * 100) as promedio'
                    )
                    ->value('promedio') ?? 0),
                'sobre_esperado' => (clone $viajes)
                    ->whereColumn(
                        'combustible_consumido_ciclo',
                        '>',
                        'galones_teoricos'
                    )
                    ->count(),
            ],
            'kilometro' => [
                'ciclos' => (clone $kilometros)->count(),
                'kilometros_recorridos' => (float) ((clone $kilometros)->sum('diferencia_kilometraje') ?? 0),
                'galones_consumidos' => (float) ((clone $kilometros)->sum('combustible_consumido_ciclo') ?? 0),
                'rendimiento_promedio' => (float) ((clone $kilometros)->whereNotNull('kilometros_por_galon')->avg('kilometros_por_galon') ?? 0),
                'mejor_rendimiento' => (float) ((clone $kilometros)->max('kilometros_por_galon') ?? 0),
                'menor_rendimiento' => (float) ((clone $kilometros)->where('kilometros_por_galon', '>', 0)->min('kilometros_por_galon') ?? 0),
            ],
            'hora' => [
                'ciclos' => (clone $horas)->count(),
                'horas_operadas' => (float) ((clone $horas)->sum('diferencia_horometro') ?? 0),
                'galones_consumidos' => (float) ((clone $horas)->sum('combustible_consumido_ciclo') ?? 0),
                'consumo_promedio' => (float) ((clone $horas)->whereNotNull('galones_por_hora')->avg('galones_por_hora') ?? 0),
                'menor_consumo' => (float) ((clone $horas)->where('galones_por_hora', '>', 0)->min('galones_por_hora') ?? 0),
                'mayor_consumo' => (float) ((clone $horas)->max('galones_por_hora') ?? 0),
            ],
        ];
    }

    private function determinarTipoResumen(array $modelosMedicion): string
    {
        if (count($modelosMedicion) !== 1) {
            return 'mixto';
        }

        return match ($modelosMedicion[0]) {
            Abastecimiento::MODELO_GALONES_VIAJE => 'viaje',
            Abastecimiento::MODELO_GALONES_KILOMETRO => 'kilometro',
            Abastecimiento::MODELO_GALONES_HORA => 'hora',
            default => 'mixto',
        };
    }

    private function obtenerEmpresasSelector(
        bool $esUsuarioDieselCop,
        ?Empresa $empresaUsuario
    ): Collection {
        if (! $esUsuarioDieselCop) {
            return collect([$empresaUsuario])->filter();
        }

        return Empresa::query()
            ->whereHas('abastecimientos', fn (Builder $query) => $query->registrados())
            ->orderByRaw('COALESCE(nombre_comercial, nombre_legal)')
            ->get();
    }

    private function obtenerUnidadesSelector(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds
    ): Collection {
        return Unidad::query()
            ->with('empresa')
            ->whereHas('abastecimientos', fn (Builder $query) => $query->registrados())
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) => $query->where('empresa_id', $empresaUsuarioId)
            )
            ->when(
                $empresaIds !== [],
                fn (Builder $query) => $query->whereIn('empresa_id', $empresaIds)
            )
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();
    }

    private function obtenerMotoristasSelector(
        bool $esUsuarioDieselCop,
        int|string|null $empresaUsuarioId,
        array $empresaIds
    ): Collection {
        return Motorista::query()
            ->with('empresa')
            ->whereHas('abastecimientos', fn (Builder $query) => $query->registrados())
            ->when(
                ! $esUsuarioDieselCop,
                fn (Builder $query) => $query->where('empresa_id', $empresaUsuarioId)
            )
            ->when(
                $empresaIds !== [],
                fn (Builder $query) => $query->whereIn('empresa_id', $empresaIds)
            )
            ->orderBy('empresa_id')
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();
    }

    private function normalizarIdsSeleccionados(
        array $ids,
        mixed $idIndividual = null
    ): array {
        if ($ids === [] && ! is_null($idIndividual) && $idIndividual !== '') {
            $ids = [$idIndividual];
        }

        return collect($ids)
            ->filter(fn ($id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizarModelosSeleccionados(
        array $modelos,
        mixed $modeloIndividual = null
    ): array {
        if ($modelos === [] && is_string($modeloIndividual) && $modeloIndividual !== '') {
            $modelos = [$modeloIndividual];
        }

        $permitidos = [
            Abastecimiento::MODELO_GALONES_KILOMETRO,
            Abastecimiento::MODELO_GALONES_HORA,
            Abastecimiento::MODELO_GALONES_VIAJE,
        ];

        return collect($modelos)
            ->filter(
                fn ($modelo): bool => is_string($modelo)
                    && in_array($modelo, $permitidos, true)
            )
            ->unique()
            ->values()
            ->all();
    }

    private function filtrarIdsPermitidos(
        array $ids,
        Collection $idsPermitidos
    ): array {
        $permitidos = $idsPermitidos
            ->map(fn ($id): int => (int) $id)
            ->all();

        return collect($ids)
            ->filter(fn ($id): bool => in_array((int) $id, $permitidos, true))
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}