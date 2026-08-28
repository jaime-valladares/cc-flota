<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Unidad;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
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

    public function pdf(Request $request): Response
    {
        abort_unless($request->boolean('consultar'), 404);

        /** @var User $usuario */
        $usuario = Auth::user();
        $filtros = $this->validarFiltros($request, $usuario);
        $unidades = $this->consultaResultados($usuario, $filtros)
            ->orderBy('empresa_id')
            ->orderBy('placa')
            ->get();

        $unidades->each(fn (Unidad $unidad) => $this->agregarRendimientoTeorico($unidad));

        return Pdf::loadView('reportes.unidades.pdf-general', [
            'unidades' => $unidades,
            'resumen' => [
                'resultados' => $unidades->count(),
                'registradas' => $unidades->where('estado', 'registrada')->count(),
                'activas' => $unidades->where('estado', 'activa')->count(),
                'operables' => $unidades->filter(fn (Unidad $unidad): bool => $unidad->es_operable)->count(),
            ],
            'filtrosAplicados' => $this->describirFiltros($filtros, $usuario),
            'alcance' => $usuario->esDieselCop()
                ? 'Todas las empresas autorizadas'
                : $unidades->first()?->empresa?->nombre_comercial
                    ?? $unidades->first()?->empresa?->nombre_legal
                    ?? 'Empresa del usuario',
            'generadoEn' => now(),
            'logoPath' => public_path('images/cc-flota/logo.png'),
        ])->setPaper('a4', 'landscape')->download(
            'reporte-unidades-'.now()->format('Y-m-d').'.pdf'
        );
    }

    public function showPdf(Unidad $unidad): Response
    {
        $datos = $this->prepararFicha($unidad);

        return Pdf::loadView('reportes.unidades.pdf-ficha', $datos + [
            'generadoEn' => now(),
            'logoPath' => public_path('images/cc-flota/logo.png'),
        ])->setPaper('a4', 'portrait')->download(
            'ficha-unidad-'.$this->sanitizarNombreArchivo($unidad->placa).'-'.now()->format('Y-m-d').'.pdf'
        );
    }

    private function prepararReporte(Request $request): array
    {
        /** @var User $usuario */
        $usuario = Auth::user();
        $esDieselCop = $usuario->esDieselCop();
        $filtros = $this->validarFiltros($request, $usuario);
        extract($filtros);
        $hayConsulta = $request->boolean('consultar');

        $hayFiltros = $busqueda !== ''
            || ($esDieselCop && $empresaIds !== [])
            || $unidadIds !== []
            || ! is_null($estado)
            || ! is_null($disponibilidad)
            || ! is_null($modeloMedicion);

        $empresas = Empresa::query()
            ->when(! $esDieselCop, fn (Builder $query) => $query->whereKey($usuario->empresa_id))
            ->orderByRaw('COALESCE(nombre_comercial, nombre_legal)')
            ->get();

        $selectorQuery = Unidad::query()
            ->select(['id', 'empresa_id', 'placa'])
            ->with(['empresa:id,nombre_comercial,nombre_legal'])
            ->when(! $esDieselCop, fn (Builder $query) => $query->where('empresa_id', $usuario->empresa_id));

        if ($empresaIds !== []) {
            $selectorQuery->whereIn('empresa_id', $empresaIds);
        }

        $unidadesSelector = $selectorQuery->orderBy('empresa_id')->orderBy('placa')->get();

        if (! $hayConsulta) {
            $unidades = new LengthAwarePaginator([], 0, self::POR_PAGINA, 1, ['path' => $request->url()]);
            $resumen = ['total' => 0, 'registradas' => 0, 'activas' => 0, 'inactivas' => 0, 'operables' => 0];

            return compact('unidades', 'empresas', 'unidadesSelector', 'empresaIds', 'unidadIds', 'busqueda', 'estado', 'disponibilidad', 'modeloMedicion', 'esDieselCop', 'hayConsulta', 'hayFiltros', 'resumen')
                + ['modelosMedicion' => $this->modelosMedicion()];
        }

        $resultadosQuery = $this->consultaResultados($usuario, $filtros);
        $resumen = $this->resumir(clone $resultadosQuery);
        $unidades = $resultadosQuery->orderBy('empresa_id')->orderBy('placa')
            ->paginate(self::POR_PAGINA)->withQueryString();

        $unidades->getCollection()->each(fn (Unidad $unidad) => $this->agregarRendimientoTeorico($unidad));

        return compact('unidades', 'empresas', 'unidadesSelector', 'empresaIds', 'unidadIds', 'busqueda', 'estado', 'disponibilidad', 'modeloMedicion', 'esDieselCop', 'hayConsulta', 'hayFiltros', 'resumen')
            + ['modelosMedicion' => $this->modelosMedicion()];
    }

    private function validarFiltros(Request $request, User $usuario): array
    {
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

        if (! $usuario->esDieselCop()) {
            $tenant = (int) $usuario->empresa_id;

            abort_if(
                collect($empresaIds)->contains(
                    fn (int $id): bool => $id !== $tenant
                ),
                403
            );

            abort_if(
                Unidad::query()
                    ->whereIn('id', $unidadIds)
                    ->where('empresa_id', '!=', $tenant)
                    ->exists(),
                403
            );

            $empresaIds = [$tenant];
        }

        return compact('busqueda', 'empresaIds', 'unidadIds', 'estado', 'disponibilidad', 'modeloMedicion');
    }

    private function consultaResultados(User $usuario, array $filtros): Builder
    {
        extract($filtros);
        $resultadosQuery = Unidad::query()
            ->with(['empresa', 'licencia', 'puntosSeguridad'])
            ->when(! $usuario->esDieselCop(), fn (Builder $query) => $query->where('empresa_id', $usuario->empresa_id));

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

        return $resultadosQuery;
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

    private function describirFiltros(array $filtros, User $usuario): array
    {
        $descripcion = [];

        if ($filtros['busqueda'] !== '') {
            $descripcion['Búsqueda'] = $filtros['busqueda'];
        }

        if ($filtros['empresaIds'] !== []) {
            $descripcion['Empresa'] = Empresa::query()
                ->whereIn('id', $filtros['empresaIds'])
                ->orderByRaw('COALESCE(nombre_comercial, nombre_legal)')
                ->get()
                ->map(fn (Empresa $empresa) => $empresa->nombre_comercial ?: $empresa->nombre_legal)
                ->implode(', ');
        }

        if ($filtros['unidadIds'] !== []) {
            $descripcion['Nombre / Placa'] = Unidad::query()
                ->whereIn('id', $filtros['unidadIds'])
                ->when(! $usuario->esDieselCop(), fn (Builder $query) => $query->where('empresa_id', $usuario->empresa_id))
                ->orderBy('placa')
                ->pluck('placa')
                ->implode(', ');
        }

        if ($filtros['estado']) {
            $descripcion['Estado'] = ucfirst($filtros['estado']);
        }

        if ($filtros['disponibilidad']) {
            $descripcion['Disponibilidad'] = $filtros['disponibilidad'] === 'operable'
                ? 'Operable'
                : 'No operable';
        }

        if ($filtros['modeloMedicion']) {
            $descripcion['Modelo'] = $this->modelosMedicion()[$filtros['modeloMedicion']];
        }

        return $descripcion;
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
            'galones_viaje' => 'Según ruta (gal/viaje)',
            default => 'Dato pendiente',
        };

        $unidad->setAttribute('rendimiento_teorico_reporte', $rendimiento);
    }

    private function sanitizarNombreArchivo(string $valor): string
    {
        $nombre = preg_replace('/[^A-Za-z0-9_-]+/', '-', $valor) ?? 'unidad';

        return trim($nombre, '-') ?: 'unidad';
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
