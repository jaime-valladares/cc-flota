<?php
namespace App\Http\Controllers\Reportes;
use App\Models\Abastecimiento;
class RendimientoKmGalonController extends RendimientoConsumoController
{
    protected function modelo(): string{return Abastecimiento::MODELO_KILOMETROS_GALON;}
    protected function carpeta(): string{return 'rendimiento-km-galon';}
    protected function datosPresentacion(): array{return['titulo'=>'Rendimiento en kilómetros por galón','tituloCorto'=>'km/gal','rutaBase'=>'reportes.rendimiento-km-galon','variableLabel'=>'Kilómetros recorridos','variableUnidad'=>'km','campoVariable'=>'diferencia_kilometraje','rendimientoLabel'=>'Rendimiento km/gal','rendimientoEsperadoCampo'=>'rendimiento_teorico_km_galon_snapshot','rendimientoRealCampo'=>'kilometros_por_galon','rendimientoUnidad'=>'km/gal'];}
}
