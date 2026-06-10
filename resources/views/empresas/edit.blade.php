<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar empresa
            </h2>

            <a href="{{ route('empresas.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Volver al listado
            </a>
        </div>
    </x-slot>

    <div class="cc-form-wrapper">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="cc-card">
                <h3 class="cc-title">
                    Editar empresa cliente
                </h3>

                <form method="POST" action="{{ route('empresas.update', $empresa) }}">
                    @csrf
                    @method('PUT')

                    @include('empresas._form', [
                        'empresa' => $empresa,
                        'submitLabel' => 'Actualizar empresa',
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>