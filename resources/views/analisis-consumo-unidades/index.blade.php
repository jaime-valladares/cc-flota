<x-app-layout>
    <div class="cc-page-wrapper">
        <div
            class="cc-content-container cc-operational-container cc-va-scope cc-va-analytics"
        >
            @include(
                'analisis-consumo-unidades.partials.contenido',
                [
                    'esVentana' => false,
                ]
            )
        </div>
    </div>
</x-app-layout>
