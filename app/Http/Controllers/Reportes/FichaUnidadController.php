<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FichaUnidadController extends Controller
{
    private const POR_PAGINA = 10;

    public function index(Request $request): View
    {
        return view('reportes.unidades.index', $this->prepararReporte($request));
    }

    public function ventana(Request $request): View
    {
        return view(
            'reportes.unidades.index-ventana',
            $this->prepararReporte($request)
        );
    }

    public function show(Unidad $unidad): View
    {
        return view(
            'reportes.unidades.show',
            $this->prepararFicha($unidad)
        );
    }

    public function showVentana(Unidad $unidad): View
    {
        return view(
            'reportes.unidades.show-ventana',
            $this->prepararFicha($unidad)
        );
    }

    private function prepararReporte(Request $request): array
    {
        /** @var User $usuario */
        $usuario = Auth::user();
        $esDieselCop = $usuario->esDieselCop();

        $validated = $request->validate([
            'busqueda' => ['nullable', 'string', 'max:150'],
            'empresa_ids' => ['nullable', 'array'],
            'empresa_ids.*' => ['integer', 'distinct', 'exists:empresas,id'],
            'unidad_ids' => ['nullable', 'array'],
            'unidad_ids.*' => ['integer', 'distinct', 'exists:unidades,id'],
            'estado' => [
                'nullable',
                Rule::in(['registrada', 'activa', 'inactiva']),
            ],
            'disponibilidad' => [
                'nullable',
                Rule::in(['operable', 'no_operable']),
            ],
            'modelo_medicion' => [
                'nullable',
                Rule::in(array_keys($this->modelosMedicion())),
            ],
        ]);

        $busqueda = trim((string) ($validated['busqueda'] ?? ''));
        $empresaIds = $this->normalizarIds($validated['empresa_ids'] ?? []);
        $unidadIds = $this->normalizarIds($validated['unidad_ids'] ?? []);
        $estado = $validated['estado'] ?? null;
        $disponibilidad = $validated['disponibilidad'] ?? null;
        $modeloMedicion = $validated['modelo_medicion'] ?? null;
        $hayConsulta = $request->boolean('consultar');

        if (! $esDieselCop) {
            $empresaIds = [(int) $usuario->empresa_id];
        }

        $hayFiltros = $busqueda !== ''
            || ($esDieselCop && $empresaIds !== [])
            || $unidadIds !== []
            || ! is_null($estado)
            || ! is_null($disponibilidad)
            || ! is_null($modeloMedicion);

        $empresas = Empresa::query()
            ->when(
                ! $esDieselCop,
                fn (Builder $query) => $query->whereKey($usuario->empresa_id)
            )
            ->orderByRaw('COALESCE(nombre_comercial, nombre_legal)')
            ->get();

        $baseQuery = Unidad::query()
            ->with(['empresa', 'licencia', 'puntosSeguridad'])
            ->when(
                ! $esDieselCop,
                fn (Builder $query) => $query->where('empresa_id', $usuario->empresa_id)
            );

        $selectorQuery = Unidad::query()
            ->select(['id', 'empresa_id', 'placa'])
            ->with([
                'empresa:id,nombre_comercial,nombre_legal',
            ])
            ->when(
                ! $esDieselCop,
                fn (Builder $query) => $query->where('empresa_id', $usuario->empresa_id)
            );

        if ($empresaIds !== []) {
            $selectorQuery->whereIn('empresa_id', $empresaIds);
        }

        $unidadesSelector = $selectorQuery
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();

        if (! $hayConsulta) {
            $unidades = new LengthAwarePaginator(
                [],
                0,
                self::POR_PAGINA,
                1,
                ['path' => $request->url()]
            );
            $resumen = [
                'total' => 0,
                'registradas' => 0,
                'activas' => 0,
                'inactivas' => 0,
                'operables' => 0,
            ];

            return compact(
                'unidades',
                'empresas',
                'unidadesSelector',
                'empresaIds',
                'unidadIds',
                'busqueda',
                'estado',
                'disponibilidad',
                'modeloMedicion',
                'esDieselCop',
                'hayConsulta',
                'hayFiltros',
                'resumen'
            ) + [
                'modelosMedicion' => $this->modelosMedicion(),
            ];
        }

        $resultadosQuery = clone $baseQuery;
        $this->aplicarFiltrosConsulta(
            $resultadosQuery,
            $busqueda,
            $empresaIds,
            $unidadIds,
            $estado,
            $modeloMedicion
        );

        if ($disponibilidad) {
            $idsDisponibles = (clone $resultadosQuery)
                ->lazyById(200)
                ->filter(
                    fn (Unidad $unidad): bool => $disponibilidad === 'operable'
                        ? $unidad->es_operable
                        : ! $unidad->es_operable
                )
                ->pluck('id')
                ->all();

            if ($idsDisponibles === []) {
                $resultadosQuery->whereRaw('1 = 0');
            } else {
                $resultadosQuery->whereIn('id', $idsDisponibles);
            }
        }

        $resumen = $this->resumir(clone $resultadosQuery);
        $unidades = $resultadosQuery
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $unidades->getCollection()->each(
            fn (Unidad $unidad) => $this->agregarRendimientoTeorico($unidad)
        );

        return compact(
            'unidades',
            'empresas',
            'unidadesSelector',
            'empresaIds',
            'unidadIds',
            'busqueda',
            'estado',
            'disponibilidad',
            'modeloMedicion',
            'esDieselCop',
            'hayConsulta',
            'hayFiltros',
            'resumen'
        ) + [
            'modelosMedicion' => $this->modelosMedicion(),
        ];
    }

    private function aplicarFiltrosConsulta(
        Builder $query,
        string $busqueda,
        array $empresaIds,
        array $unidadIds,
        ?string $estado,
        ?string $modeloMedicion
    ): void {
        if ($busqueda !== '') {
            $query->where(function (Builder $filtro) use ($busqueda): void {
                $termino = '%'.$busqueda.'%';

                $filtro->where('placa', 'like', $termino)
                    ->orWhereHas('empresa', function (Builder $empresa) use ($termino): void {
                        $empresa->where('nombre_comercial', 'like', $termino)
                            ->orWhere('nombre_legal', 'like', $termino);
                    });
            });
        }

        if ($empresaIds !== []) {
            $query->whereIn('empresa_id', $empresaIds);
        }

        if ($unidadIds !== []) {
            $query->whereIn('id', $unidadIds);
        }

        if ($estado) {
            $query->where('estado', $estado);
        }

        if ($modeloMedicion) {
            $query->where('modelo_medicion', $modeloMedicion);
        }
    }

    private function resumir(Builder $query): array
    {
        return [
            'total' => (clone $query)->count(),
            'registradas' => (clone $query)
                ->where('estado', 'registrada')->count(),
            'activas' => (clone $query)
                ->where('estado', 'activa')->count(),
            'inactivas' => (clone $query)
                ->where('estado', 'inactiva')->count(),
            'operables' => (clone $query)
                ->lazyById(200)
                ->filter(fn (Unidad $unidad): bool => $unidad->es_operable)
                ->count(),
        ];
    }

    private function normalizarIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function modelosMedicion(): array
    {
        return [
            'kilometros_galon' => 'Kilómetros por galón',
            'galones_hora' => 'Galones por hora',
            'galones_viaje' => 'Galones por viaje',
        ];
    }

    private function prepararFicha(Unidad $unidad): array
    {
        /** @var User $usuario */
        $usuario = Auth::user();

        $this->autorizarUnidad($usuario, $unidad);
        $unidad->load([
            'empresa',
            'licencia.tanquesCubiertos',
            'tanquesUnidad',
            'puntosSeguridad.marchamoActual',
        ]);
        $this->agregarRendimientoTeorico($unidad);

        return compact('unidad');
    }

    private function agregarRendimientoTeorico(Unidad $unidad): void
    {
        $rendimiento = match ($unidad->modelo_medicion) {
            'kilometros_galon' => is_null($unidad->rendimiento_teorico_km_galon)
                ? 'Dato pendiente'
                : number_format((float) $unidad->rendimiento_teorico_km_galon, 2).' km/gal',
            'galones_hora' => is_null($unidad->rendimiento_teorico_gal_hora)
                ? 'Dato pendiente'
                : number_format((float) $unidad->rendimiento_teorico_gal_hora, 2).' gal/hora',
            'galones_viaje' => 'Según ruta',
            default => 'Dato pendiente',
        };

        $unidad->setAttribute('rendimiento_teorico_reporte', $rendimiento);
    }

    private function autorizarUnidad(User $usuario, Unidad $unidad): void
    {
        if (! $usuario->esUsuarioEmpresa()) {
            return;
        }

        abort_unless(
            (int) $unidad->empresa_id === (int) $usuario->empresa_id,
            403,
            'No tiene autorización para consultar esta unidad.'
        );
    }
}
