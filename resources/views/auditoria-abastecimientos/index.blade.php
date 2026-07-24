<x-app-layout>
    <div class="cc-page-wrapper">
        <div
            class="cc-content-container"
            style="max-width: 80rem;"
        >
            @include(
                'auditoria-abastecimientos.partials.contenido',
                [
                    'esVentana' => false,
                ]
            )
        </div>
    </div>
</x-app-layout>