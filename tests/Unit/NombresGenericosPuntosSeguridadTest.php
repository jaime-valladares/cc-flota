<?php

use App\Support\PlantillasPuntosSeguridad;

it('generaliza los puntos de módulo sin alterar la entrada del motor', function () {
    $plantillas = [
        'plantilla_1_tanque',
        'plantilla_2_tanques',
        'plantilla_3_tanques',
    ];
    $marcaEspecifica = implode('', ['Cum', 'mins']);

    foreach ($plantillas as $codigoPlantilla) {
        $puntos = collect(
            PlantillasPuntosSeguridad::porPlantilla($codigoPlantilla)
        )->keyBy('codigo_punto');

        expect($puntos['OTR-34']['nombre_punto'])
            ->toBe('Base módulo entrada')
            ->and($puntos['OTR-35']['nombre_punto'])
            ->toBe('Base módulo salida')
            ->and($puntos['ALIM-15']['nombre_punto'])
            ->toBe('Entrada motor')
            ->and(json_encode($puntos->all(), JSON_UNESCAPED_UNICODE))
            ->not->toContain($marcaEspecifica);
    }
});

it('limita la migración a renombrar los códigos esperados', function () {
    $migraciones = glob(
        dirname(__DIR__, 2)
        .'/database/migrations/*generalize_*_security_point_names.php'
    );

    expect($migraciones)->toHaveCount(1);

    $contenido = file_get_contents($migraciones[0]);

    expect($contenido)
        ->toContain("'OTR-34'")
        ->toContain("'OTR-35'")
        ->toContain("->where('codigo_punto', \$codigo)")
        ->toContain("->where('nombre_punto', \$nombres[\$origen])")
        ->toContain("'nombre_punto' => \$nombres[\$destino]")
        ->toContain('DB::transaction')
        ->toContain('lockForUpdate')
        ->toContain('RuntimeException')
        ->not->toContain("'ALIM-15'")
        ->not->toContain("'unidad_id' =>")
        ->not->toContain("'codigo_punto' =>")
        ->not->toContain("'orden' =>")
        ->not->toContain("'plantilla_origen' =>")
        ->not->toContain("'tipo_punto' =>")
        ->not->toContain("'estado' =>")
        ->not->toContain("'marchamo_actual_id' =>");
});
