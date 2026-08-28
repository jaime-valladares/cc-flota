<?php
namespace App\Http\Controllers\Reportes;
use App\Models\Abastecimiento;
class RendimientoGalonesHoraController extends RendimientoConsumoController
{
    protected function modelo(): string{return Abastecimiento::MODELO_GALONES_HORA;}
    protected function carpeta(): string{return 'rendimiento-galones-hora';}
    protected function datosPresentacion(): array{return['titulo'=>'Rendimiento en galones por hora','tituloCorto'=>'gal/h','rutaBase'=>'reportes.rendimiento-galones-hora','ciclosLabel'=>'Ciclos / operaciones evaluadas','variableLabel'=>'Horas trabajadas','variableUnidad'=>'h','campoVariable'=>'diferencia_horometro','rendimientoLabel'=>'Consumo gal/h','rendimientoEsperadoCampo'=>'rendimiento_teorico_gal_hora_snapshot','rendimientoRealCampo'=>'galones_por_hora','rendimientoUnidad'=>'gal/h'];}
}
