<x-app-layout>
    <div class="cc-page-wrapper cc-va-scope cc-va-analytics">
        <div
            class="cc-content-container cc-operational-container"
        >
            @include(
                'auditoria-marchamos.partials.contenido',
                [
                    'esVentana' => false,
                ]
            )
        </div>
    </div>
</x-app-layout>