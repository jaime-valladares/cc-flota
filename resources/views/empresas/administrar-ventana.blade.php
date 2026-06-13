<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar empresa | CC-Flota</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-content-container">
                    <div class="cc-card">

                        <div class="cc-card-header">
                            <div>
                                <h3 class="cc-title">
                                    Administrar empresa
                                </h3>
                                <p class="cc-subtitle">
                                    Localice una empresa cliente para consultar su ficha, editar sus datos o gestionar su estado administrativo.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('empresas.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="GET" action="{{ route('empresas.administrar.ventana') }}" class="mb-6">
                            <div class="border border-gray-200 rounded-lg p-5 bg-gray-50">

                                <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                                    <div class="cc-form-section-title">
                                        Búsqueda administrativa
                                    </div>
                                    <div class="cc-form-section-note">
                                        Ingrese criterios para localizar la empresa que desea administrar.
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">

                                    <div class="lg:col-span-4 cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>

                                        @if ($esUsuarioDieselCop)
                                            <select id="empresa_id" name="empresa_id" class="cc-input">
                                                <option value="">Todas</option>

                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}" @selected((string) $empresaId === (string) $empresaOpcion->id)>
                                                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <select id="empresa_id" name="empresa_id" class="cc-input" disabled>
                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}" selected>
                                                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif

                                        @error('empresa_id')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-3 cc-field">
                                        <label for="nit">
                                            NIT
                                        </label>
                                        <input
                                            id="nit"
                                            name="nit"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $nit }}"
                                            maxlength="17"
                                            placeholder="0000-000000-000-0"
                                        >

                                        @error('nit')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-2 cc-field">
                                        <label for="estado">
                                            Estado
                                        </label>
                                        <select id="estado" name="estado" class="cc-input">
                                            <option value="">Seleccione</option>
                                            <option value="activa" @selected($estado === 'activa')>
                                                Activas
                                            </option>
                                            <option value="inactiva" @selected($estado === 'inactiva')>
                                                Inactivas
                                            </option>
                                        </select>

                                        @error('estado')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-3 flex items-center gap-3">
                                        <button type="submit" class="cc-btn-primary">
                                            Buscar
                                        </button>

                                        <a href="{{ route('empresas.administrar.ventana') }}" class="cc-btn-secondary">
                                            Resetear
                                        </a>
                                    </div>

                                </div>

                                <div class="mt-4 border-t border-gray-200 pt-4">
                                    <p class="text-sm text-gray-500 italic">
                                        @if ($esUsuarioDieselCop)
                                            Seleccione una empresa, use Todas o agregue otro criterio para localizar empresas.
                                        @else
                                            La búsqueda administrativa está limitada automáticamente a la empresa asignada a su usuario.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </form>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h4 class="text-base font-black text-gray-900">
                                    Resultado administrativo
                                </h4>

                                <p class="text-sm text-gray-500 italic">
                                    @if (! $hayFiltros)
                                        Seleccione una empresa, NIT o estado para buscar una empresa.
                                    @elseif ($empresas->total() === 0)
                                        No se encontraron empresas con los criterios seleccionados.
                                    @elseif ($empresas->total() === 1)
                                        Se encontró 1 empresa para administrar.
                                    @else
                                        Se encontraron {{ $empresas->total() }} empresas para administrar.
                                    @endif
                                </p>
                            </div>

                            @if ($hayFiltros && $empresas->total() > 0)
                                <div class="text-sm text-gray-500">
                                    Mostrando
                                    <span class="font-bold text-gray-700">{{ $empresas->firstItem() }}</span>
                                    -
                                    <span class="font-bold text-gray-700">{{ $empresas->lastItem() }}</span>
                                    de
                                    <span class="font-bold text-gray-700">{{ $empresas->total() }}</span>
                                </div>
                            @endif
                        </div>

                        @if (! $hayFiltros)
                            <div class="border border-dashed border-gray-300 rounded-lg p-6 bg-gray-50">
                                <h5 class="text-base font-black text-gray-900">
                                    Búsqueda pendiente
                                </h5>
                                <p class="mt-1 text-sm text-gray-500 italic">
                                    Los resultados permanecerán vacíos hasta que localice una empresa por empresa, NIT o estado.
                                </p>
                            </div>
                        @elseif ($empresas->isEmpty())
                            <div class="border border-dashed border-gray-300 rounded-lg p-6 bg-gray-50">
                                <h5 class="text-base font-black text-gray-900">
                                    Sin resultados
                                </h5>
                                <p class="mt-1 text-sm text-gray-500 italic">
                                    No hay empresas que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($empresas as $empresa)
                                    <article class="border border-gray-200 rounded-xl bg-white p-5 shadow-sm">
                                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">

                                            <div class="lg:col-span-4 min-w-0">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <h5 class="text-xl font-black text-gray-900 cc-cell-truncate">
                                                        {{ $empresa->nombre_legal }}
                                                    </h5>

                                                    @if ($empresa->estado === 'activa')
                                                        <span class="cc-badge cc-badge-active">
                                                            Activa
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Inactiva
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="mt-1 text-sm text-gray-500 cc-cell-truncate">
                                                    {{ $empresa->nombre_comercial ?: 'Sin nombre comercial registrado' }}
                                                </div>
                                            </div>

                                            <div class="lg:col-span-2 min-w-0">
                                                <div class="text-xs font-black text-gray-500 uppercase tracking-wider">
                                                    NIT
                                                </div>

                                                <div class="mt-1 font-bold text-gray-900">
                                                    {{ $empresa->nit }}
                                                </div>
                                            </div>

                                            <div class="lg:col-span-3 min-w-0">
                                                <div class="text-xs font-black text-gray-500 uppercase tracking-wider">
                                                    Contacto
                                                </div>

                                                <div class="mt-1 font-bold text-gray-900 cc-cell-truncate">
                                                    {{ $empresa->poc_nombre }}
                                                </div>

                                                <div class="text-sm text-gray-500 cc-cell-truncate">
                                                    {{ $empresa->poc_email }}
                                                </div>
                                            </div>

                                            <div class="lg:col-span-3">
                                                <div class="flex items-center justify-end gap-3">
                                                    <a href="{{ route('empresas.show', $empresa) }}" class="cc-btn-primary">
                                                        Ver ficha
                                                    </a>

                                                    <a href="{{ route('empresas.edit', $empresa) }}" class="cc-btn-secondary">
                                                        Editar
                                                    </a>
                                                </div>
                                            </div>

                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                {{ $empresas->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <script>
            function formatNit(value) {
                const digits = value.replace(/\D/g, '').slice(0, 14);

                if (digits.length <= 4) {
                    return digits;
                }

                if (digits.length <= 10) {
                    return `${digits.slice(0, 4)}-${digits.slice(4)}`;
                }

                if (digits.length <= 13) {
                    return `${digits.slice(0, 4)}-${digits.slice(4, 10)}-${digits.slice(10)}`;
                }

                return `${digits.slice(0, 4)}-${digits.slice(4, 10)}-${digits.slice(10, 13)}-${digits.slice(13)}`;
            }

            const nitInput = document.getElementById('nit');

            if (nitInput) {
                nitInput.addEventListener('input', function () {
                    this.value = formatNit(this.value);
                });
            }
        </script>
    </body>
</html>