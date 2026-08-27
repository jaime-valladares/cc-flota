<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="width: 100%; max-width: 80rem;">
            <div class="cc-card">
                @include('abastecimientos.ciclos.show-contenido', ['modoVentana' => false])
            </div>
        </div>
    </div>
</x-app-layout>
