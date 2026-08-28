<?php

test('usuarios expone filtros modernos y estado de empresa como ultima columna', function () {
    $filtros = file_get_contents(resource_path('views/usuarios/_filtros.blade.php'));

    expect($filtros)
        ->toContain('data-filter-tipo-usuario')
        ->toContain('data-filter-estado-usuario')
        ->toContain('data-filter-estado-empresa')
        ->toContain('Estado Usuario')
        ->toContain('Estado Empresa')
        ->toContain('cc-users-filter-actions');

    foreach (['index.blade.php', 'index-ventana.blade.php'] as $vista) {
        $html = file_get_contents(resource_path('views/usuarios/'.$vista));
        expect($html)->toMatch('/<th[^>]*>Estado Usuario<\/th>\s*<th>Estado Empresa<\/th>/');
    }
});

test('consulta usuarios ordena filtros resumen y tabla y usa paneles compactos', function () {
    $filtros = file_get_contents(resource_path('views/usuarios/_filtros.blade.php'));
    expect($filtros)->toContain('cc-filter-multiselect-compact')->toContain('$mostrarEstadoEmpresa ?? true');

    foreach (['index.blade.php', 'index-ventana.blade.php'] as $vista) {
        $html = file_get_contents(resource_path('views/usuarios/'.$vista));
        expect(strpos($html, "@include('usuarios._filtros')"))->toBeLessThan(strpos($html, 'data-usuarios-summary'))
            ->and(strpos($html, 'data-usuarios-summary'))->toBeLessThan(strpos($html, '<table'));
    }

    foreach (['administrar.blade.php', 'administrar-ventana.blade.php'] as $vista) {
        expect(file_get_contents(resource_path('views/usuarios/'.$vista)))->toContain('$mostrarEstadoEmpresa = false');
    }

    $css = file_get_contents(resource_path('css/app.css'));
    expect($css)->toContain('.cc-filter-multiselect-compact .cc-filter-multiselect-menu')->not->toContain('.cc-filter-multiselect-compact .cc-filter-multiselect-toggle');
});

test('unidades y administrar empresas usan el filtro moderno de estado', function () {
    foreach (['index.blade.php', 'index-ventana.blade.php', 'administrar.blade.php', 'administrar-ventana.blade.php'] as $vista) {
        $html = file_get_contents(resource_path('views/unidades/'.$vista));
        expect($html)->toContain('data-filter-estado-administrativo');
    }

    foreach (['administrar.blade.php', 'administrar-ventana.blade.php'] as $vista) {
        $html = file_get_contents(resource_path('views/empresas/'.$vista));
        expect($html)->toContain('data-filter-estado');
    }
});

test('resumen de consulta unidades queda despues del formulario y antes de resultados', function () {
    foreach (['index.blade.php', 'index-ventana.blade.php'] as $vista) {
        $html = file_get_contents(resource_path('views/unidades/'.$vista));
        $resumen = strpos($html, 'data-unidades-summary');

        expect($resumen)
            ->toBeGreaterThan(strpos($html, '</form>'))
            ->toBeLessThan(strpos($html, 'Mostrando'));
    }
});
