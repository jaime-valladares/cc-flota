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

abstract class RendimientoConsumoController extends Controller
{
    private const POR_PAGINA = 10;
    abstract protected function modelo(): string;
    abstract protected function carpeta(): string;
    abstract protected function datosPresentacion(): array;

    public function index(Request $request): View { return view("reportes.{$this->carpeta()}.index", $this->prepararReporte($request)); }
    public function ventana(Request $request): View { return view("reportes.{$this->carpeta()}.index-ventana", $this->prepararReporte($request)); }
    public function show(Abastecimiento $ciclo): View { return view("reportes.{$this->carpeta()}.show", $this->prepararDetalle($ciclo)); }
    public function showVentana(Abastecimiento $ciclo): View { return view("reportes.{$this->carpeta()}.show-ventana", $this->prepararDetalle($ciclo)); }

    private function prepararReporte(Request $request): array
    {
        /** @var User $usuario */ $usuario = Auth::user();
        $filtros = $this->validarFiltros($request, $usuario); extract($filtros);
        $esDieselCop = $usuario->esDieselCop(); $hayConsulta = $request->boolean('consultar');
        $empresas = Empresa::query()->when(! $esDieselCop, fn (Builder $q) => $q->whereKey($usuario->empresa_id))->orderByRaw('COALESCE(nombre_comercial, nombre_legal)')->get();
        $unidadesSelector = Unidad::query()->select(['id','empresa_id','placa'])->with('empresa:id,nombre_comercial,nombre_legal')->where('modelo_medicion',$this->modelo())
            ->when(! $esDieselCop,fn(Builder $q)=>$q->where('empresa_id',$usuario->empresa_id))->when($empresaIds!==[],fn(Builder $q)=>$q->whereIn('empresa_id',$empresaIds))->orderBy('empresa_id')->orderBy('placa')->get();
        $motoristasSelector = Motorista::query()->when(! $esDieselCop,fn(Builder $q)=>$q->where('empresa_id',$usuario->empresa_id))->when($empresaIds!==[],fn(Builder $q)=>$q->whereIn('empresa_id',$empresaIds))->orderBy('nombres')->orderBy('apellidos')->get();
        if (! $hayConsulta) { $ciclos=new LengthAwarePaginator([],0,self::POR_PAGINA,1,['path'=>$request->url()]); $resumen=$this->resumenVacio(); }
        else { $consulta=$this->consultaFiltrada($usuario,$filtros); $resumen=$this->resumir(clone $consulta); $ciclos=$consulta->orderByDesc('fecha_hora_abastecimiento')->orderByDesc('id')->paginate(self::POR_PAGINA)->withQueryString(); $ciclos->getCollection()->each(fn(Abastecimiento $c)=>$this->enriquecerCiclo($c)); }
        return array_merge(compact('ciclos','resumen','empresas','unidadesSelector','motoristasSelector','empresaIds','unidadIds','motoristaIds','fechaDesde','fechaHasta','resultado','busqueda','esDieselCop','hayConsulta'),$this->datosPresentacion());
    }

    private function validarFiltros(Request $request, User $usuario): array
    {
        foreach (['empresa_ids', 'unidad_ids', 'motorista_ids'] as $campo) {
            if ($request->has($campo)) {
                $request->merge([$campo => array_values(array_filter((array) $request->input($campo), fn ($id) => $id !== null && $id !== ''))]);
            }
        }
        $v=$request->validate(['empresa_ids'=>['nullable','array'],'empresa_ids.*'=>['integer','distinct','exists:empresas,id'],'unidad_ids'=>['nullable','array'],'unidad_ids.*'=>['integer','distinct','exists:unidades,id'],'motorista_ids'=>['nullable','array'],'motorista_ids.*'=>['integer','distinct','exists:motoristas,id'],'fecha_desde'=>['nullable','date_format:Y-m-d'],'fecha_hasta'=>['nullable','date_format:Y-m-d','after_or_equal:fecha_desde'],'resultado'=>['nullable',Rule::in(['ahorro','sobreconsumo','en_objetivo'])],'busqueda'=>['nullable','string','max:150']]);
        $empresaIds=$this->normalizarIds($v['empresa_ids']??[]); $unidadIds=$this->normalizarIds($v['unidad_ids']??[]); $motoristaIds=$this->normalizarIds($v['motorista_ids']??[]);
        if (! $usuario->esDieselCop()) { $tenant=(int)$usuario->empresa_id; abort_if(collect($empresaIds)->contains(fn(int $id)=>$id!==$tenant),403); abort_if(Unidad::query()->whereIn('id',$unidadIds)->where('empresa_id','!=',$tenant)->exists(),403); abort_if(Motorista::query()->whereIn('id',$motoristaIds)->where('empresa_id','!=',$tenant)->exists(),403); $empresaIds=[$tenant]; }
        abort_if(Unidad::query()->whereIn('id',$unidadIds)->where('modelo_medicion','!=',$this->modelo())->exists(),404);
        return compact('empresaIds','unidadIds','motoristaIds')+['fechaDesde'=>$v['fecha_desde']??null,'fechaHasta'=>$v['fecha_hasta']??null,'resultado'=>$v['resultado']??null,'busqueda'=>trim((string)($v['busqueda']??''))];
    }

    private function consultaFiltrada(User $usuario,array $f): Builder
    {
        extract($f); $modelo=$this->modelo();
        $q=Abastecimiento::query()->with(['abastecimientoAnterior','rutas'])->where('estado',Abastecimiento::ESTADO_REGISTRADO)->where('modelo_medicion',$modelo)->whereNotNull('abastecimiento_anterior_id')
            ->whereHas('abastecimientoAnterior',fn(Builder $a)=>$a->where('estado',Abastecimiento::ESTADO_REGISTRADO)->where('modelo_medicion',$modelo))
            ->when(! $usuario->esDieselCop(),fn(Builder $t)=>$t->where('empresa_id',$usuario->empresa_id));
        $q->when($empresaIds!==[],fn(Builder $x)=>$x->whereIn('empresa_id',$empresaIds))->when($unidadIds!==[],fn(Builder $x)=>$x->whereIn('unidad_id',$unidadIds))->when($motoristaIds!==[],fn(Builder $x)=>$x->whereIn('motorista_id',$motoristaIds))
            ->when($fechaDesde,fn(Builder $x)=>$x->where('fecha_hora_abastecimiento','>=',Carbon::parse($fechaDesde)->startOfDay()))->when($fechaHasta,fn(Builder $x)=>$x->where('fecha_hora_abastecimiento','<',Carbon::parse($fechaHasta)->addDay()->startOfDay()))
            ->when($resultado==='ahorro',fn(Builder $x)=>$x->whereColumn('consumo_real_ciclo','<','consumo_teorico_ciclo'))->when($resultado==='sobreconsumo',fn(Builder $x)=>$x->whereColumn('consumo_real_ciclo','>','consumo_teorico_ciclo'))->when($resultado==='en_objetivo',fn(Builder $x)=>$x->whereColumn('consumo_real_ciclo','=','consumo_teorico_ciclo'));
        if($busqueda!=='')$q->where(function(Builder $x)use($busqueda):void{$t="%{$busqueda}%";$x->where('empresa_nombre_snapshot','like',$t)->orWhere('unidad_placa_snapshot','like',$t)->orWhere('unidad_marca_snapshot','like',$t)->orWhere('motorista_nombre_snapshot','like',$t);if(ctype_digit($busqueda))$x->orWhere('id',(int)$busqueda);}); return $q;
    }

    private function prepararDetalle(Abastecimiento $ciclo): array
    {
        /** @var User $usuario */ $usuario=Auth::user(); if($usuario->esUsuarioEmpresa())abort_unless((int)$ciclo->empresa_id===(int)$usuario->empresa_id,403);
        abort_unless($ciclo->estado===Abastecimiento::ESTADO_REGISTRADO&&$ciclo->modelo_medicion===$this->modelo()&&$ciclo->abastecimiento_anterior_id,404); $ciclo->load(['abastecimientoAnterior','rutas']); $a=$ciclo->abastecimientoAnterior;
        abort_unless($a?->estado===Abastecimiento::ESTADO_REGISTRADO&&$a->modelo_medicion===$this->modelo()&&(int)$a->empresa_id===(int)$ciclo->empresa_id&&(int)$a->unidad_id===(int)$ciclo->unidad_id,404); $this->enriquecerCiclo($ciclo); return array_merge(compact('ciclo'),$this->datosPresentacion());
    }

    private function enriquecerCiclo(Abastecimiento $c): void
    { $real=(float)$c->consumo_real_ciclo;$teorico=(float)$c->consumo_teorico_ciclo;$dif=$real-$teorico;$resultado=$real<$teorico?'Ahorro':($real>$teorico?'Sobreconsumo':'En objetivo');$costo=$real>0?(float)$c->costo_combustible_consumido_ciclo/$real:0.0;$impacto=$resultado==='En objetivo'?0.0:abs($dif)*$costo;foreach(['resultado_reporte'=>$resultado,'diferencia_absoluta_reporte'=>abs($dif),'costo_efectivo_galon_reporte'=>$costo,'impacto_economico_reporte'=>$impacto,'impacto_neto_reporte'=>$resultado==='Ahorro'?$impacto:($resultado==='Sobreconsumo'?-$impacto:0.0)]as$k=>$v)$c->setAttribute($k,$v); }
    private function resumir(Builder $q): array
    { $cs=$q->get();$cs->each(fn(Abastecimiento $c)=>$this->enriquecerCiclo($c));return['ciclos'=>$cs->count(),'operacion'=>$cs->sum(fn($c)=>(float)match($this->modelo()){Abastecimiento::MODELO_GALONES_HORA=>$c->diferencia_horometro,Abastecimiento::MODELO_GALONES_VIAJE=>$c->total_viajes,default=>$c->diferencia_kilometraje}),'consumo_teorico'=>$cs->sum(fn($c)=>(float)$c->consumo_teorico_ciclo),'consumo_real'=>$cs->sum(fn($c)=>(float)$c->consumo_real_ciclo),'ahorro_galones'=>$cs->where('resultado_reporte','Ahorro')->sum('diferencia_absoluta_reporte'),'sobreconsumo_galones'=>$cs->where('resultado_reporte','Sobreconsumo')->sum('diferencia_absoluta_reporte'),'impacto_neto'=>$cs->sum('impacto_neto_reporte')]; }
    private function resumenVacio(): array{return['ciclos'=>0,'operacion'=>0,'consumo_teorico'=>0,'consumo_real'=>0,'ahorro_galones'=>0,'sobreconsumo_galones'=>0,'impacto_neto'=>0];}
    private function normalizarIds(array $ids): array{return collect($ids)->map(fn($id)=>(int)$id)->unique()->values()->all();}
}
