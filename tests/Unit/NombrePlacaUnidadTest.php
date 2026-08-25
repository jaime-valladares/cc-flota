<?php

function contenidoProyecto(string $ruta): string
{
    $contenido = file_get_contents(
        dirname(__DIR__, 2).'/'.$ruta
    );

    if ($contenido === false) {
        throw new RuntimeException(
            "No se pudo leer el archivo {$ruta}."
        );
    }

    return $contenido;
}

it('define una migración con unicidad por empresa y snapshot de 30 caracteres', function () {
    $migraciones = glob(
        dirname(__DIR__, 2)
        .'/database/migrations/*nombre_placa_unico_por_empresa.php'
    );

    expect($migraciones)->toHaveCount(1);

    $contenido = file_get_contents($migraciones[0]);

    expect($contenido)
        ->toContain("['empresa_id', 'placa']")
        ->toContain('unidades_empresa_id_placa_unique')
        ->toContain("'unidad_placa_snapshot', 30");
});

it('limita la unicidad de Nombre Placa a la empresa de la unidad', function () {
    $contenido = contenidoProyecto(
        'app/Http/Controllers/UnidadController.php'
    );

    expect($contenido)
        ->toContain("->where('empresa_id', \$empresaId)")
        ->toContain('empresaId: $empresaId')
        ->toContain('empresaId: (int) $unidad->empresa_id');
});

it('permite el mismo Nombre Placa en empresas distintas mediante el índice compuesto', function () {
    $contenido = contenidoProyecto(
        'database/migrations/2026_08_26_000001_make_nombre_placa_unico_por_empresa.php'
    );
    $up = strstr($contenido, 'public function down', true);

    expect($contenido)
        ->toContain("['empresa_id', 'placa']");
    expect($up)->not->toContain("\$table->unique('placa'");
});

it('rechaza duplicados dentro de la empresa y conserva comparación case insensitive', function () {
    $migracion = contenidoProyecto(
        'database/migrations/2026_08_26_000001_make_nombre_placa_unico_por_empresa.php'
    );

    expect($migracion)
        ->toContain("groupBy('empresa_id', 'placa')")
        ->toContain("havingRaw('COUNT(*) > 1')")
        ->not->toContain('collation');
});

it('ignora la propia unidad durante la edición', function () {
    expect(contenidoProyecto('app/Http/Controllers/UnidadController.php'))
        ->toContain('->ignore($unidad?->id)');
});

it('conserva la capitalización ingresada al crear y editar', function () {
    $contenido = contenidoProyecto(
        'app/Http/Controllers/UnidadController.php'
    );

    expect(substr_count($contenido, "'placa' => trim("))
        ->toBeGreaterThanOrEqual(2)
        ->and($contenido)
        ->not->toContain("'placa' => mb_strtoupper(");
});

it('usa ids de unidad en selectores que atraviesan empresas', function () {
    $archivos = [
        'app/Http/Controllers/UnidadController.php',
        'app/Http/Controllers/LicenciaController.php',
        'app/Http/Controllers/MarchamoController.php',
        'app/Http/Controllers/MarchamoAsignacionInicialController.php',
        'app/Http/Controllers/MarchamoReemplazoController.php',
        'app/Http/Controllers/AbastecimientoController.php',
    ];

    foreach ($archivos as $archivo) {
        expect(contenidoProyecto($archivo))
            ->not->toContain("->pluck('placa')")
            ->not->toContain("->pluck('unidades.placa')");
    }

    $vista = contenidoProyecto(
        'resources/views/unidades/index.blade.php'
    );

    expect($vista)
        ->toContain('name="unidad_ids[]"')
        ->toContain('value="{{ $unidadOpcion->id }}"')
        ->toContain('$unidadOpcion->empresa')
        ->toContain('$unidadOpcion->placa');
});

it('mantiene longitud máxima y terminología neutral en el formulario', function () {
    $contenido = contenidoProyecto(
        'resources/views/unidades/_form.blade.php'
    );

    expect($contenido)
        ->toContain('Nombre / Placa')
        ->toContain('maxlength="30"')
        ->toContain('Ej. Camión 01 o P123-456');
});

it('protege el rollback y admite snapshots de hasta 30 caracteres', function () {
    $contenido = contenidoProyecto(
        'database/migrations/2026_08_26_000001_make_nombre_placa_unico_por_empresa.php'
    );

    expect($contenido)
        ->toContain("havingRaw('COUNT(DISTINCT empresa_id) > 1')")
        ->toContain('RuntimeException')
        ->toContain("'unidad_placa_snapshot', 30")
        ->not->toContain('UPDATE abastecimientos');
});
