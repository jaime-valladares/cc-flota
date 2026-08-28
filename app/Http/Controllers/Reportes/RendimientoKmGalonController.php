<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Abastecimiento;
use App\Models\Empresa;
use App\Models\Motorista;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RendimientoKmGalonController extends Controller
{
    private const POR_PAGINA = 10;

    public function index(Request $request): View
    {
        return view('reportes.rendimiento-km-galon.index', $this->prepararReporte($request));
    }

    public function ventana(Request $request): View
    {
        return view('reportes.rendimiento-km-galon.index-ventana', $this->prepararReporte($request));
    }

    public function show(Abastecimiento $ciclo): View
    {
        return view('reportes.rendimiento-km-galon.show', $this->prepararDetalle($ciclo));
    }

    public function showVentana(Abastecimiento $ciclo): View
    {
        return view('reportes.rendimiento-km-galon.show-ventana', $this->prepararDetalle($ciclo));
    }

    private function prepararReporte(Request $request): array
    {
        /** @var User $usuario */
        $usuario = Auth::user();
        $esDieselCop = $usuario->esDieselCop();
        $filtros = $this->validarFiltros($request, $usuario);
        extract($filtros);
        $hayConsulta = $request->boolean('consultar');

        $empresas = Empresa::query()
            ->when(! $esDieselCop, fn (Builder $query) => $query->whereKey($usuario->empresa_id))
            ->orderByRaw('COALESCE(nombre_comercial, nombre_legal)')
            ->get();
        $unidadesSelector = Unidad::query()
            ->select(['id', 'empresa_id', 'placa'])
            ->with('empresa:id,nombre_comercial,nombre_legal')
            ->when(! $esDieselCop, fn (Builder $query) => $query->where('empresa_id', $usuario->empresa_id))
            ->when($empresaIds !== [], fn (Builder $query) => $query->whereIn('empresa_id', $empresaIds))
            ->orderBy('empresa_id')->orderBy('placa')->get();
        $motoristasSelector = Motorista::query()
            ->when(! $esDieselCop, fn (Builder $query) => $query->where('empresa_id', $usuario->empresa_id))
            ->when($empresaIds !== [], fn (Builder $query) => $query->whereIn('empresa_id', $empresaIds))
            ->orderBy('nombres')->orderBy('apellidos')->get();

        if (! $hayConsulta) {
            $ciclos = new LengthAwarePaginator([], 0, self::POR_PAGINA, 1, ['path' => $request->url()]);
            $resumen = $this->resumenVacio();
        } else {
            $consulta = $this->consultaFiltrada($usuario, $filtros);
            $resumen = $this->resumir(clone $consulta);
            $ciclos = $consulta->orderByDesc('fecha_hora_abastecimiento')
                ->orderByDesc('id')->paginate(self::POR_PAGINA)->withQueryString();
            $ciclos->getCollection()->each(fn (Abastecimiento $ciclo) => $this->enriquecerCiclo($ciclo));
        }

        return compact(
            'ciclos', 'resumen', 'empresas', 'unidadesSelector', 'motoristasSelector',
            'empresaIds', 'unidadIds', 'motoristaIds', 'fechaDesde', 'fechaHasta',
            'resultado', 'busqueda', 'esDieselCop', 'hayConsulta'
        );
    }

    private function validarFiltros(Request $request, User $usuario): array
    {
        $validated = $request->validate([
            'empresa_ids' => ['nullable', 'array'],
            'empresa_ids.*' => ['integer', 'distinct', 'exists:empresas,id'],
            'unidad_ids' => ['nullable', 'array'],
            'unidad_ids.*' => ['integer', 'distinct', 'exists:unidades,id'],
            'motorista_ids' => ['nullable', 'array'],
            'motorista_ids.*' => ['integer', 'distinct', 'exists:motoristas,id'],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
            'resultado' => ['nullable', Rule::in(['ahorro', 'sobreconsumo', 'en_objetivo'])],
            'busqueda' => ['nullable', 'string', 'max:150'],
        ]);

        $empresaIds = $this->normalizarIds($validated['empresa_ids'] ?? []);
        $unidadIds = $this->normalizarIds($validated['unidad_ids'] ?? []);
        $motoristaIds = $this->normalizarIds($validated['motorista_ids'] ?? []);
        $fechaDesde = $validated['fecha_desde'] ?? null;
        $fechaHasta = $validated['fecha_hasta'] ?? null;
        $resultado = $validated['resultado'] ?? null;
        $busqueda = trim((string) ($validated['busqueda'] ?? ''));

        if (! $usuario->esDieselCop()) {
            $empresaIds = [(int) $usuario->empresa_id];
        }

        return compact('empresaIds', 'unidadIds', 'motoristaIds', 'fechaDesde', 'fechaHasta', 'resultado', 'busqueda');
    }

    private function consultaFiltrada(User $usuario, array $filtros): Builder
    {
        extract($filtros);
        $query = Abastecimiento::query()
            ->with('abastecimientoAnterior')
            ->where('estado', Abastecimiento::ESTADO_REGISTRADO)
            ->where('modelo_medicion', Abastecimiento::MODELO_KILOMETROS_GALON)
            ->whereNotNull('abastecimiento_anterior_id')
            ->whereHas('abastecimientoAnterior', fn (Builder $anterior) => $anterior->where('estado', Abastecimiento::ESTADO_REGISTRADO))
            ->when(! $usuario->esDieselCop(), fn (Builder $tenant) => $tenant->where('empresa_id', $usuario->empresa_id));

        $query->when($empresaIds !== [], fn (Builder $filtro) => $filtro->whereIn('empresa_id', $empresaIds))
            ->when($unidadIds !== [], fn (Builder $filtro) => $filtro->whereIn('unidad_id', $unidadIds))
            ->when($motoristaIds !== [], fn (Builder $filtro) => $filtro->whereIn('motorista_id', $motoristaIds))
            ->when($fechaDesde, fn (Builder $filtro) => $filtro->where('fecha_hora_abastecimiento', '>=', Carbon::parse($fechaDesde)->startOfDay()))
            ->when($fechaHasta, fn (Builder $filtro) => $filtro->where('fecha_hora_abastecimiento', '<', Carbon::parse($fechaHasta)->addDay()->startOfDay()))
            ->when($resultado === 'ahorro', fn (Builder $filtro) => $filtro->whereColumn('consumo_real_ciclo', '<', 'consumo_teorico_ciclo'))
            ->when($resultado === 'sobreconsumo', fn (Builder $filtro) => $filtro->whereColumn('consumo_real_ciclo', '>', 'consumo_teorico_ciclo'))
            ->when($resultado === 'en_objetivo', fn (Builder $filtro) => $filtro->whereColumn('consumo_real_ciclo', '=', 'consumo_teorico_ciclo'));

        if ($busqueda !== '') {
            $query->where(function (Builder $filtro) use ($busqueda): void {
                $termino = '%'.$busqueda.'%';
                $filtro->where('empresa_nombre_snapshot', 'like', $termino)
                    ->orWhere('unidad_placa_snapshot', 'like', $termino)
                    ->orWhere('unidad_marca_snapshot', 'like', $termino)
                    ->orWhere('unidad_modelo_snapshot', 'like', $termino)
                    ->orWhere('motorista_nombre_snapshot', 'like', $termino);

                if (ctype_digit($busqueda)) {
                    $filtro->orWhere('id', (int) $busqueda);
                }
            });
        }

        return $query;
    }

    private function prepararDetalle(Abastecimiento $ciclo): array
    {
        /** @var User $usuario */
        $usuario = Auth::user();
        $this->autorizarTenant($usuario, $ciclo);
        abort_unless(
            $ciclo->estado === Abastecimiento::ESTADO_REGISTRADO
            && $ciclo->modelo_medicion === Abastecimiento::MODELO_KILOMETROS_GALON
            && ! is_null($ciclo->abastecimiento_anterior_id),
            404
        );

        $ciclo->load('abastecimientoAnterior');
        abort_unless($ciclo->abastecimientoAnterior?->estado === Abastecimiento::ESTADO_REGISTRADO, 404);
        $this->enriquecerCiclo($ciclo);

        return compact('ciclo');
    }

    private function autorizarTenant(User $usuario, Abastecimiento $ciclo): void
    {
        if ($usuario->esUsuarioEmpresa()) {
            abort_unless((int) $ciclo->empresa_id === (int) $usuario->empresa_id, 403);
        }
    }

    private function enriquecerCiclo(Abastecimiento $ciclo): void
    {
        $consumoReal = (float) $ciclo->consumo_real_ciclo;
        $consumoTeorico = (float) $ciclo->consumo_teorico_ciclo;
        $diferencia = $consumoReal - $consumoTeorico;
        $costoConsumido = (float) $ciclo->costo_combustible_consumido_ciclo;
        $resultado = $consumoReal < $consumoTeorico
            ? 'Ahorro'
            : ($consumoReal > $consumoTeorico ? 'Sobreconsumo' : 'En objetivo');
        $costoEfectivo = $consumoReal > 0 ? $costoConsumido / $consumoReal : 0.0;
        $impacto = abs($diferencia) * $costoEfectivo;

        $ciclo->setAttribute('resultado_reporte', $resultado);
        $ciclo->setAttribute('diferencia_absoluta_reporte', abs($diferencia));
        $ciclo->setAttribute('costo_efectivo_galon_reporte', $costoEfectivo);
        $ciclo->setAttribute('impacto_economico_reporte', $resultado === 'En objetivo' ? 0.0 : $impacto);
        $ciclo->setAttribute('impacto_neto_reporte', $resultado === 'Ahorro' ? $impacto : ($resultado === 'Sobreconsumo' ? -$impacto : 0.0));
    }

    private function resumir(Builder $query): array
    {
        $ciclos = $query->get();
        $ciclos->each(fn (Abastecimiento $ciclo) => $this->enriquecerCiclo($ciclo));

        return [
            'ciclos' => $ciclos->count(),
            'kilometros' => $ciclos->sum(fn (Abastecimiento $ciclo) => (float) $ciclo->diferencia_kilometraje),
            'consumo_teorico' => $ciclos->sum(fn (Abastecimiento $ciclo) => (float) $ciclo->consumo_teorico_ciclo),
            'consumo_real' => $ciclos->sum(fn (Abastecimiento $ciclo) => (float) $ciclo->consumo_real_ciclo),
            'ahorro_galones' => $ciclos->where('resultado_reporte', 'Ahorro')->sum('diferencia_absoluta_reporte'),
            'sobreconsumo_galones' => $ciclos->where('resultado_reporte', 'Sobreconsumo')->sum('diferencia_absoluta_reporte'),
            'impacto_neto' => $ciclos->sum('impacto_neto_reporte'),
        ];
    }

    private function resumenVacio(): array
    {
        return ['ciclos' => 0, 'kilometros' => 0, 'consumo_teorico' => 0, 'consumo_real' => 0, 'ahorro_galones' => 0, 'sobreconsumo_galones' => 0, 'impacto_neto' => 0];
    }

    private function normalizarIds(array $ids): array
    {
        return collect($ids)->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }
}
