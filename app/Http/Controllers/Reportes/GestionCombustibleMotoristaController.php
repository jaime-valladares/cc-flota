<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Abastecimiento;
use App\Models\Empresa;
use App\Models\Motorista;
use App\Models\Unidad;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GestionCombustibleMotoristaController extends Controller
{
    private const POR_PAGINA = 20;

    private const MODELOS = [
        Abastecimiento::MODELO_KILOMETROS_GALON => 'Km/Gal',
        Abastecimiento::MODELO_GALONES_HORA => 'Gal/Hora',
        Abastecimiento::MODELO_GALONES_VIAJE => 'Gal/Viaje',
    ];

    public function index(Request $request): View
    {
        return view('reportes.gestion-combustible-motorista.index', $this->prepararReporte($request));
    }

    public function ventana(Request $request): View
    {
        return view('reportes.gestion-combustible-motorista.index-ventana', $this->prepararReporte($request));
    }

    public function show(Request $request, Motorista $motorista): View
    {
        return view('reportes.gestion-combustible-motorista.show', $this->prepararDetalle($request, $motorista));
    }

    public function showVentana(Request $request, Motorista $motorista): View
    {
        return view('reportes.gestion-combustible-motorista.show-ventana', $this->prepararDetalle($request, $motorista));
    }

    public function showPdf(Request $request, Motorista $motorista): Response
    {
        $datos = $this->prepararDetalle($request, $motorista, true);

        return Pdf::loadView('reportes.gestion-combustible-motorista.pdf-detalle', $datos + [
            'periodoEvaluado' => $this->periodoEvaluado($datos),
            'generadoEn' => now(),
            'logoPath' => public_path('images/cc-flota/logo.png'),
        ])->setPaper('a4', 'landscape')->download('gestion-combustible-motorista-'.$motorista->id.'.pdf');
    }

    public function pdf(Request $request): Response
    {
        abort_unless($request->boolean('consultar'), 404);
        /** @var User $usuario */
        $usuario = Auth::user();
        $filtros = $this->validarFiltros($request, $usuario);
        $base = $this->consultaBase($usuario, $filtros);
        $resumen = $this->resumen(clone $base);
        $motoristas = $this->consultaAgrupada(clone $base, $request)->get();

        return Pdf::loadView('reportes.gestion-combustible-motorista.pdf-general', [
            'motoristas' => $motoristas,
            'resumen' => $resumen,
            'filtrosAplicados' => $this->describirFiltros($filtros, $usuario),
            'periodoEvaluado' => $this->periodoEvaluado($filtros),
            'modelos' => self::MODELOS,
            'generadoEn' => now(),
            'logoPath' => public_path('images/cc-flota/logo.png'),
        ])->setPaper('a4', 'landscape')->download('gestion-combustible-motorista.pdf');
    }

    private function prepararReporte(Request $request): array
    {
        /** @var User $usuario */
        $usuario = Auth::user();
        $filtros = $this->validarFiltros($request, $usuario);
        $hayConsulta = $request->boolean('consultar');
        $esDieselCop = $usuario->esDieselCop();

        $empresas = Empresa::query()
            ->when(! $esDieselCop, fn (Builder $q) => $q->whereKey($usuario->empresa_id))
            ->orderByRaw('COALESCE(nombre_comercial, nombre_legal)')->get();
        $unidadesSelector = Unidad::query()->select(['id', 'empresa_id', 'placa'])
            ->when(! $esDieselCop, fn (Builder $q) => $q->where('empresa_id', $usuario->empresa_id))
            ->when($filtros['empresaIds'] !== [], fn (Builder $q) => $q->whereIn('empresa_id', $filtros['empresaIds']))
            ->orderBy('placa')->get();
        $motoristasSelector = Motorista::query()
            ->when(! $esDieselCop, fn (Builder $q) => $q->where('empresa_id', $usuario->empresa_id))
            ->when($filtros['empresaIds'] !== [], fn (Builder $q) => $q->whereIn('empresa_id', $filtros['empresaIds']))
            ->orderBy('nombres')->orderBy('apellidos')->get();

        if (! $hayConsulta) {
            $motoristas = new LengthAwarePaginator([], 0, self::POR_PAGINA, 1, ['path' => $request->url()]);
            $resumen = $this->resumenVacio();
        } else {
            $base = $this->consultaBase($usuario, $filtros);
            $resumen = $this->resumen(clone $base);
            $motoristas = $this->consultaAgrupada(clone $base, $request)
                ->paginate(self::POR_PAGINA)->withQueryString();
        }

        return compact('motoristas', 'resumen', 'empresas', 'unidadesSelector', 'motoristasSelector', 'hayConsulta', 'esDieselCop')
            + $filtros + ['modelos' => self::MODELOS];
    }

    private function prepararDetalle(Request $request, Motorista $motorista, bool $paraPdf = false): array
    {
        /** @var User $usuario */
        $usuario = Auth::user();
        if (! $usuario->esDieselCop()) {
            abort_unless((int) $motorista->empresa_id === (int) $usuario->empresa_id, 403);
        }
        $filtros = $this->validarFiltros($request, $usuario);
        if ($filtros['motoristaIds'] !== [] && ! in_array((int) $motorista->id, $filtros['motoristaIds'], true)) {
            abort(404);
        }
        $filtros['motoristaIds'] = [(int) $motorista->id];
        $base = $this->consultaBase($usuario, $filtros);
        abort_unless((clone $base)->exists(), 404);
        $resumen = $this->resumen(clone $base);
        $desglose = $this->consultaDesglose(clone $base)->get();
        $empresasHistoricas = (clone $base)->distinct()->orderBy('empresa_nombre_snapshot')->pluck('empresa_nombre_snapshot');
        $consultaCiclos = (clone $base)->with('abastecimientoAnterior')->orderByDesc('fecha_hora_abastecimiento')->orderByDesc('id');
        $ciclos = $paraPdf ? $consultaCiclos->get() : $consultaCiclos->paginate(15)->withQueryString();
        ($paraPdf ? $ciclos : $ciclos->getCollection())->each(fn (Abastecimiento $ciclo) => $this->enriquecerCiclo($ciclo));
        $impactoMilKm = $resumen['kilometros'] > 0 ? $resumen['impacto_neto'] / $resumen['kilometros'] * 1000 : null;

        return compact('motorista', 'resumen', 'desglose', 'empresasHistoricas', 'ciclos', 'impactoMilKm')
            + $filtros + ['modelos' => self::MODELOS];
    }

    private function validarFiltros(Request $request, User $usuario): array
    {
        foreach (['empresa_ids', 'motorista_ids', 'unidad_ids', 'modelo_medicion'] as $campo) {
            if ($request->has($campo)) {
                $request->merge([$campo => array_values(array_filter((array) $request->input($campo), fn ($v) => $v !== null && $v !== ''))]);
            }
        }
        $v = $request->validate([
            'empresa_ids' => ['nullable', 'array'], 'empresa_ids.*' => ['integer', 'distinct', 'exists:empresas,id'],
            'motorista_ids' => ['nullable', 'array'], 'motorista_ids.*' => ['integer', 'distinct', 'exists:motoristas,id'],
            'unidad_ids' => ['nullable', 'array'], 'unidad_ids.*' => ['integer', 'distinct', 'exists:unidades,id'],
            'modelo_medicion' => ['nullable', 'array'], 'modelo_medicion.*' => ['string', 'distinct', Rule::in(array_keys(self::MODELOS))],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
            'resultado' => ['nullable', Rule::in(['ahorro', 'sobreconsumo', 'en_objetivo'])],
            'orden' => ['nullable', Rule::in(['motorista', 'ciclos', 'kilometros', 'consumo', 'impacto', 'favorables'])],
            'direccion' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $empresaIds = $this->ids($v['empresa_ids'] ?? []);
        $motoristaIds = $this->ids($v['motorista_ids'] ?? []);
        $unidadIds = $this->ids($v['unidad_ids'] ?? []);
        $modelosSeleccionados = array_values(array_unique($v['modelo_medicion'] ?? []));
        if (! $usuario->esDieselCop()) {
            $tenant = (int) $usuario->empresa_id;
            abort_if(collect($empresaIds)->contains(fn (int $id) => $id !== $tenant), 403);
            abort_if(Motorista::query()->whereIn('id', $motoristaIds)->where('empresa_id', '!=', $tenant)->exists(), 403);
            abort_if(Unidad::query()->whereIn('id', $unidadIds)->where('empresa_id', '!=', $tenant)->exists(), 403);
            $empresaIds = [$tenant];
        }

        return compact('empresaIds', 'motoristaIds', 'unidadIds', 'modelosSeleccionados') + [
            'fechaDesde' => $v['fecha_desde'] ?? null, 'fechaHasta' => $v['fecha_hasta'] ?? null,
            'resultado' => $v['resultado'] ?? null, 'orden' => $v['orden'] ?? 'impacto', 'direccion' => $v['direccion'] ?? 'desc',
        ];
    }

    private function consultaBase(User $usuario, array $f): Builder
    {
        $q = Abastecimiento::query()->where('estado', Abastecimiento::ESTADO_REGISTRADO)
            ->whereIn('modelo_medicion', array_keys(self::MODELOS))->whereNotNull('abastecimiento_anterior_id')
            ->whereNotNull('consumo_real_ciclo')->whereNotNull('consumo_teorico_ciclo')
            ->whereExists(function ($apertura): void {
                $apertura->selectRaw('1')->from('abastecimientos as apertura')
                    ->whereColumn('apertura.id', 'abastecimientos.abastecimiento_anterior_id')
                    ->where('apertura.estado', Abastecimiento::ESTADO_REGISTRADO)
                    ->whereColumn('apertura.modelo_medicion', 'abastecimientos.modelo_medicion')
                    ->whereColumn('apertura.empresa_id', 'abastecimientos.empresa_id')
                    ->whereColumn('apertura.unidad_id', 'abastecimientos.unidad_id');
            })->when(! $usuario->esDieselCop(), fn (Builder $x) => $x->where('empresa_id', $usuario->empresa_id));

        return $q->when($f['empresaIds'] !== [], fn (Builder $x) => $x->whereIn('empresa_id', $f['empresaIds']))
            ->when($f['motoristaIds'] !== [], fn (Builder $x) => $x->whereIn('motorista_id', $f['motoristaIds']))
            ->when($f['unidadIds'] !== [], fn (Builder $x) => $x->whereIn('unidad_id', $f['unidadIds']))
            ->when($f['modelosSeleccionados'] !== [], fn (Builder $x) => $x->whereIn('modelo_medicion', $f['modelosSeleccionados']))
            ->when($f['fechaDesde'], fn (Builder $x) => $x->where('fecha_hora_abastecimiento', '>=', Carbon::parse($f['fechaDesde'])->startOfDay()))
            ->when($f['fechaHasta'], fn (Builder $x) => $x->where('fecha_hora_abastecimiento', '<', Carbon::parse($f['fechaHasta'])->addDay()->startOfDay()))
            ->when($f['resultado'] === 'ahorro', fn (Builder $x) => $x->whereColumn('consumo_real_ciclo', '<', 'consumo_teorico_ciclo'))
            ->when($f['resultado'] === 'sobreconsumo', fn (Builder $x) => $x->whereColumn('consumo_real_ciclo', '>', 'consumo_teorico_ciclo'))
            ->when($f['resultado'] === 'en_objetivo', fn (Builder $x) => $x->whereColumn('consumo_real_ciclo', '=', 'consumo_teorico_ciclo'));
    }

    private function consultaAgrupada(Builder $base, Request $request): Builder
    {
        $q = $base->selectRaw($this->selectAgregado('motorista_id, MAX(motorista_nombre_snapshot) AS motorista_nombre, GROUP_CONCAT(DISTINCT modelo_medicion ORDER BY modelo_medicion SEPARATOR \',\') AS modelos'))
            ->groupBy('motorista_id');
        $columnas = ['motorista' => 'motorista_nombre', 'ciclos' => 'ciclos', 'kilometros' => 'kilometros', 'consumo' => 'consumo_real', 'impacto' => 'impacto_neto', 'favorables' => 'porcentaje_favorable'];
        $orden = $columnas[$request->input('orden', 'impacto')] ?? 'impacto_neto';

        return $q->orderBy($orden, $request->input('direccion') === 'asc' ? 'asc' : 'desc')->orderBy('motorista_nombre');
    }

    private function consultaDesglose(Builder $base): Builder
    {
        return $base->selectRaw($this->selectAgregado("modelo_medicion, CASE modelo_medicion WHEN 'kilometros_galon' THEN SUM(COALESCE(diferencia_kilometraje,0)) WHEN 'galones_hora' THEN SUM(COALESCE(diferencia_horometro,0)) ELSE SUM(COALESCE(total_viajes,0)) END AS actividad"))
            ->groupBy('modelo_medicion')->orderBy('modelo_medicion');
    }

    private function selectAgregado(string $prefijo): string
    {
        $costo = 'CASE WHEN consumo_real_ciclo > 0 THEN costo_combustible_consumido_ciclo / consumo_real_ciclo ELSE 0 END';
        $impacto = "CASE WHEN consumo_real_ciclo < consumo_teorico_ciclo THEN (consumo_teorico_ciclo-consumo_real_ciclo)*($costo) WHEN consumo_real_ciclo > consumo_teorico_ciclo THEN -(consumo_real_ciclo-consumo_teorico_ciclo)*($costo) ELSE 0 END";

        return "$prefijo, COUNT(*) AS ciclos, COUNT(DISTINCT unidad_id) AS unidades, SUM(COALESCE(diferencia_kilometraje,0)) AS kilometros, SUM(consumo_teorico_ciclo) AS consumo_teorico, SUM(consumo_real_ciclo) AS consumo_real, SUM(COALESCE(costo_combustible_consumido_ciclo,0)) AS costo_total, SUM(CASE WHEN consumo_real_ciclo < consumo_teorico_ciclo THEN consumo_teorico_ciclo-consumo_real_ciclo ELSE 0 END) AS galones_ahorrados, SUM(CASE WHEN consumo_real_ciclo > consumo_teorico_ciclo THEN consumo_real_ciclo-consumo_teorico_ciclo ELSE 0 END) AS sobreconsumo, SUM(CASE WHEN consumo_real_ciclo < consumo_teorico_ciclo THEN (consumo_teorico_ciclo-consumo_real_ciclo)*($costo) ELSE 0 END) AS ahorro_economico, SUM(CASE WHEN consumo_real_ciclo > consumo_teorico_ciclo THEN (consumo_real_ciclo-consumo_teorico_ciclo)*($costo) ELSE 0 END) AS costo_sobreconsumo, SUM($impacto) AS impacto_neto, SUM(CASE WHEN consumo_real_ciclo < consumo_teorico_ciclo THEN 1 ELSE 0 END) AS ciclos_ahorro, SUM(CASE WHEN consumo_real_ciclo > consumo_teorico_ciclo THEN 1 ELSE 0 END) AS ciclos_sobreconsumo, SUM(CASE WHEN consumo_real_ciclo = consumo_teorico_ciclo THEN 1 ELSE 0 END) AS ciclos_objetivo, SUM(CASE WHEN consumo_real_ciclo < consumo_teorico_ciclo THEN 1 ELSE 0 END)*100.0/COUNT(*) AS porcentaje_favorable";
    }

    private function resumen(Builder $base): array
    {
        $fila = $base->selectRaw($this->selectAgregado('COUNT(DISTINCT motorista_id) AS motoristas'))->first();
        if (! $fila || (int) $fila->ciclos === 0) {
            return $this->resumenVacio();
        }

        return collect($fila->getAttributes())->map(fn ($v) => is_numeric($v) ? (float) $v : $v)->all()
            + ['costo_promedio' => (float) $fila->consumo_real > 0 ? (float) $fila->costo_total / (float) $fila->consumo_real : null];
    }

    private function resumenVacio(): array
    {
        return ['motoristas' => 0, 'ciclos' => 0, 'unidades' => 0, 'kilometros' => 0, 'consumo_teorico' => 0, 'consumo_real' => 0, 'costo_total' => 0, 'costo_promedio' => null, 'galones_ahorrados' => 0, 'sobreconsumo' => 0, 'ahorro_economico' => 0, 'costo_sobreconsumo' => 0, 'impacto_neto' => 0, 'ciclos_ahorro' => 0, 'ciclos_sobreconsumo' => 0, 'ciclos_objetivo' => 0, 'porcentaje_favorable' => 0];
    }

    private function enriquecerCiclo(Abastecimiento $c): void
    {
        $real = (float) $c->consumo_real_ciclo;
        $teorico = (float) $c->consumo_teorico_ciclo;
        $costo = $real > 0 ? (float) $c->costo_combustible_consumido_ciclo / $real : 0;
        $resultado = $real < $teorico ? 'Ahorro' : ($real > $teorico ? 'Sobreconsumo' : 'En objetivo');
        $impacto = abs($real - $teorico) * $costo;
        $c->setAttribute('resultado_reporte', $resultado);
        $c->setAttribute('diferencia_absoluta_reporte', abs($real - $teorico));
        $c->setAttribute('impacto_economico_reporte', $impacto);
    }

    private function describirFiltros(array $f, User $usuario): array
    {
        $d = [];
        if ($f['empresaIds']) {
            $d['Empresa'] = Empresa::whereIn('id', $f['empresaIds'])->get()->map(fn ($e) => $e->nombre_comercial ?: $e->nombre_legal)->implode(', ');
        }
        if ($f['motoristaIds']) {
            $d['Motorista'] = Motorista::whereIn('id', $f['motoristaIds'])->when(! $usuario->esDieselCop(), fn ($q) => $q->where('empresa_id', $usuario->empresa_id))->get()->pluck('nombre_completo')->implode(', ');
        }
        if ($f['unidadIds']) {
            $d['Unidad'] = Unidad::whereIn('id', $f['unidadIds'])->when(! $usuario->esDieselCop(), fn ($q) => $q->where('empresa_id', $usuario->empresa_id))->pluck('placa')->implode(', ');
        }
        if ($f['modelosSeleccionados']) {
            $d['Modelo'] = collect($f['modelosSeleccionados'])->map(fn ($m) => self::MODELOS[$m])->implode(', ');
        }
        if ($f['fechaDesde']) {
            $d['Fecha desde'] = Carbon::parse($f['fechaDesde'])->format('d/m/Y');
        }
        if ($f['fechaHasta']) {
            $d['Fecha hasta'] = Carbon::parse($f['fechaHasta'])->format('d/m/Y');
        }
        if ($f['resultado']) {
            $d['Resultado'] = ['ahorro' => 'Ahorro', 'sobreconsumo' => 'Sobreconsumo', 'en_objetivo' => 'En objetivo'][$f['resultado']];
        }

        return $d;
    }

    private function periodoEvaluado(array $filtros): string
    {
        $desde = $filtros['fechaDesde'] ? Carbon::parse($filtros['fechaDesde'])->format('d/m/Y') : 'Inicio histórico';
        $hasta = $filtros['fechaHasta'] ? Carbon::parse($filtros['fechaHasta'])->format('d/m/Y') : 'Actualidad';

        return $desde.' – '.$hasta;
    }

    private function ids(array $ids): array
    {
        return collect($ids)->map(fn ($id) => (int) $id)->unique()->values()->all();
    }
}
