<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Listado de empresas registradas
                        </h3>
                        <p class="cc-subtitle">
                            Administración de empresas cliente registradas en CC-Flota.
                        </p>
                    </div>

                    <a href="{{ route('empresas.create') }}" class="cc-btn-primary cc-btn-wide">
                        Nueva empresa
                    </a>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($empresas->isEmpty())
                    <p class="cc-empty-state">
                        Todavía no hay empresas registradas.
                    </p>
                @else
                    <div class="cc-table-wrapper">
                        <table class="cc-table">
                            <thead>
                                <tr>
                                    <th>Nombre legal</th>
                                    <th>Nombre comercial</th>
                                    <th>NIT</th>
                                    <th>POC</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($empresas as $empresa)
                                    <tr>
                                        <td>
                                            {{ $empresa->nombre_legal }}
                                        </td>

                                        <td>
                                            {{ $empresa->nombre_comercial ?? '—' }}
                                        </td>

                                        <td>
                                            {{ $empresa->nit }}
                                        </td>

                                        <td>
                                            {{ $empresa->poc_nombre }}
                                        </td>

                                        <td>
                                            @if ($empresa->estado === 'activa')
                                                <span class="cc-badge cc-badge-active">
                                                    Activa
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactiva
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('empresas.edit', $empresa) }}" class="cc-btn-primary">
                                                    Editar
                                                </a>

                                                @if ($empresa->estado === 'activa')
                                                    <form method="POST"
                                                          action="{{ route('empresas.inactivar', $empresa) }}"
                                                          onsubmit="return confirm('¿Seguro que deseas inactivar esta empresa?');">
                                                        @csrf
                                                        @method('PATCH')

                                                        <input type="hidden"
                                                               name="motivo_inactivacion"
                                                               value="Inactivación administrativa desde listado">

                                                        <button type="submit" class="cc-btn-danger">
                                                            Inactivar
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST"
                                                          action="{{ route('empresas.reactivar', $empresa) }}"
                                                          onsubmit="return confirm('¿Seguro que deseas reactivar esta empresa?');">
                                                        @csrf
                                                        @method('PATCH')

                                                        <button type="submit" class="cc-btn-success">
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
</x-app-layout>