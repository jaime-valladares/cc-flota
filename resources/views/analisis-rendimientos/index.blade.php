<x-app-layout>
    <div class="cc-page-wrapper">
        <div
            class="cc-content-container"
            style="width: 100%; max-width: 80rem;"
        >
            @include(
                'analisis-rendimientos.partials.contenido',
                [
                    'esVentana' => false,
                ]
            )
        </div>
    </div>
</x-app-layout>