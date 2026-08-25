<?php

use App\Http\Controllers\AnalisisConsumoUnidadController;
use App\Http\Controllers\UnidadController;
use App\Models\Abastecimiento;
use App\Models\Unidad;
use Illuminate\Support\Collection;

it('define kilometros_galon como el modelo kilométrico oficial', function () {
    expect(Abastecimiento::MODELO_KILOMETROS_GALON)
        ->toBe('kilometros_galon');

    $unidad = new Unidad([
        'modelo_medicion' => 'kilometros_galon',
    ]);

    expect($unidad->modelo_medicion_texto)
        ->toBe('Kilómetros por galón');
});

it('ofrece kilometros_galon en el catálogo de unidades', function () {
    $metodo = new ReflectionMethod(
        UnidadController::class,
        'modelosMedicion'
    );

    $modelos = $metodo->invoke(new UnidadController);

    expect($modelos)
        ->toHaveKey('kilometros_galon', 'Kilómetros por galón')
        ->not->toHaveKey('galones_kilometro');
});

it('interpreta kilometros por galon como mayor es mejor', function () {
    $controlador = new AnalisisConsumoUnidadController;

    $rendimiento = new ReflectionMethod(
        AnalisisConsumoUnidadController::class,
        'calcularRendimientoConsolidado'
    );
    $mejor = new ReflectionMethod(
        AnalisisConsumoUnidadController::class,
        'mejorResultado'
    );
    $peor = new ReflectionMethod(
        AnalisisConsumoUnidadController::class,
        'peorResultado'
    );
    $tendencia = new ReflectionMethod(
        AnalisisConsumoUnidadController::class,
        'determinarTendencia'
    );

    $modelo = Abastecimiento::MODELO_KILOMETROS_GALON;
    $resultados = new Collection([8.0, 10.0]);

    expect($rendimiento->invoke($controlador, $modelo, 100.0, 10.0))
        ->toBe(10.0)
        ->and($mejor->invoke($controlador, $resultados, $modelo))
        ->toBe(10.0)
        ->and($peor->invoke($controlador, $resultados, $modelo))
        ->toBe(8.0)
        ->and($tendencia->invoke($controlador, $resultados, $modelo))
        ->toBe('mejora');
});
