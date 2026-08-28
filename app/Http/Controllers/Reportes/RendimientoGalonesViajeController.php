<?php
namespace App\Http\Controllers\Reportes;
use App\Models\Abastecimiento;
class RendimientoGalonesViajeController extends RendimientoConsumoController
{
    protected function modelo(): string{return Abastecimiento::MODELO_GALONES_VIAJE;}
    protected function carpeta(): string{return 'rendimiento-galones-viaje';}
    protected function datosPresentacion(): array{return['titulo'=>'Rendimiento en galones por viaje','tituloCorto'=>'gal/viaje','rutaBase'=>'reportes.rendimiento-galones-viaje','ciclosLabel'=>'Ciclos / operaciones evaluadas','variableLabel'=>'Viajes evaluados','variableUnidad'=>'viajes','campoVariable'=>'total_viajes','rendimientoLabel'=>'Consumo por viaje','rendimientoEsperadoCampo'=>null,'rendimientoRealCampo'=>null,'rendimientoUnidad'=>'gal/viaje'];}
}
