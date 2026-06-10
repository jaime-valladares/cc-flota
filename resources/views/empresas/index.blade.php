<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Empresas
            </h2>

            <a href="{{ route('empresas.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Nueva empresa
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if (session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    <h3 class="text-lg font-semibold mb-4">
                        Listado de empresas registradas
                    </h3>

                    @if ($empresas->isEmpty())
                        <p class="text-gray-600">
                            Todavía no hay empresas registradas.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Nombre legal</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Nombre comercial</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">NIT</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">POC</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Estado</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($empresas as $empresa)
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900">
                                                {{ $empresa->nombre_legal }}
                                            </td>

                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                {{ $empresa->nombre_comercial ?? '—' }}
                                            </td>

                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                {{ $empresa->nit }}
                                            </td>

                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                {{ $empresa->poc_nombre }}
                                            </td>

                                            <td class="px-4 py-2 text-sm">
                                                @if ($empresa->estado === 'activa')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                        Activa
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                        Inactiva
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-2 text-sm">
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('empresas.edit', $empresa) }}"
                                                    class="inline-flex items-center px-3 py-1 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                                    Editar
                                                    </a>

                                                    @if ($empresa->estado === 'activa')
                                                        <form method="POST" action="{{ route('empresas.inactivar', $empresa) }}"
                                                            onsubmit="return confirm('¿Seguro que deseas inactivar esta empresa?');">
                                                            @csrf
                                                            @method('PATCH')

                                                            <input type="hidden"
                                                                name="motivo_inactivacion"
                                                                value="Inactivación administrativa desde listado">

                                                            <button type="submit"
                                                                    class="inline-flex items-center px-3 py-1 bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-800">
                                                                Inactivar
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('empresas.reactivar', $empresa) }}"
                                                            onsubmit="return confirm('¿Seguro que deseas reactivar esta empresa?');">
                                                            @csrf
                                                            @method('PATCH')

                                                            <button type="submit"
                                                                    class="inline-flex items-center px-3 py-1 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">
                                                                Reactivar
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</x-app-layout>