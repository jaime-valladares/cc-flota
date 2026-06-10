<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Nueva empresa
            </h2>

            <a href="{{ route('empresas.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Volver al listado
            </a>
        </div>
    </x-slot>

    <style>
        .cc-form-wrapper {
            padding: 2rem 1.25rem;
        }

        .cc-card {
            background: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
            padding: 1.5rem;
        }

        .cc-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 1.5rem;
        }

        .cc-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }

        @media (min-width: 768px) {
            .cc-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .cc-col-span-2 {
                grid-column: span 2 / span 2;
            }
        }

        .cc-field label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.35rem;
        }

        .cc-required {
            color: #dc2626;
        }

        .cc-input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.95rem;
            color: #111827;
            background-color: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }

        .cc-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
        }

        .cc-error {
            margin-top: 0.35rem;
            font-size: 0.875rem;
            color: #dc2626;
        }

        .cc-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid #e5e7eb;
        }

        .cc-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #1f2937;
            color: #ffffff;
            border: 1px solid #1f2937;
            border-radius: 0.375rem;
            padding: 0.65rem 1.15rem;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
        }

        .cc-btn-primary:hover {
            background-color: #111827;
        }

        .cc-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.65rem 1.15rem;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
        }

        .cc-btn-secondary:hover {
            background-color: #f9fafb;
        }
    </style>

    <div class="cc-form-wrapper">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="cc-card">
                <h3 class="cc-title">
                    Registro de empresa cliente
                </h3>

                <form method="POST" action="{{ route('empresas.store') }}">
                    @csrf

                    <div class="cc-grid">
                        <div class="cc-field">
                            <label for="nombre_legal">
                                Nombre legal <span class="cc-required">*</span>
                            </label>
                            <input type="text"
                                   name="nombre_legal"
                                   id="nombre_legal"
                                   value="{{ old('nombre_legal') }}"
                                   required
                                   maxlength="150"
                                   class="cc-input">
                            @error('nombre_legal')
                                <p class="cc-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="cc-field">
                            <label for="nombre_comercial">
                                Nombre comercial
                            </label>
                            <input type="text"
                                   name="nombre_comercial"
                                   id="nombre_comercial"
                                   value="{{ old('nombre_comercial') }}"
                                   maxlength="150"
                                   class="cc-input">
                            @error('nombre_comercial')
                                <p class="cc-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="cc-field">
                            <label for="nit">
                                NIT <span class="cc-required">*</span>
                            </label>
                            <input type="text"
                                   name="nit"
                                   id="nit"
                                   value="{{ old('nit') }}"
                                   required
                                   maxlength="17"
                                   inputmode="numeric"
                                   autocomplete="off"
                                   class="cc-input">
                            @error('nit')
                                <p class="cc-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="cc-field">
                            <label for="telefono_empresa">
                                Teléfono empresa
                            </label>
                            <input type="text"
                                   name="telefono_empresa"
                                   id="telefono_empresa"
                                   value="{{ old('telefono_empresa') }}"
                                   maxlength="9"
                                   inputmode="numeric"
                                   autocomplete="off"
                                   class="cc-input">
                            @error('telefono_empresa')
                                <p class="cc-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="cc-field cc-col-span-2">
                            <label for="direccion">
                                Dirección
                            </label>
                            <input type="text"
                                   name="direccion"
                                   id="direccion"
                                   value="{{ old('direccion') }}"
                                   maxlength="255"
                                   class="cc-input">
                            @error('direccion')
                                <p class="cc-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="cc-field">
                            <label for="correo_empresa">
                                Correo empresa <span class="cc-required">*</span>
                            </label>
                            <input type="email"
                                   name="correo_empresa"
                                   id="correo_empresa"
                                   value="{{ old('correo_empresa') }}"
                                   required
                                   maxlength="150"
                                   class="cc-input">
                            @error('correo_empresa')
                                <p class="cc-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="cc-field">
                            <label for="poc_nombre">
                                Nombre del POC <span class="cc-required">*</span>
                            </label>
                            <input type="text"
                                   name="poc_nombre"
                                   id="poc_nombre"
                                   value="{{ old('poc_nombre') }}"
                                   required
                                   maxlength="150"
                                   class="cc-input">
                            @error('poc_nombre')
                                <p class="cc-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="cc-field">
                            <label for="poc_email">
                                Correo del POC <span class="cc-required">*</span>
                            </label>
                            <input type="email"
                                   name="poc_email"
                                   id="poc_email"
                                   value="{{ old('poc_email') }}"
                                   required
                                   maxlength="150"
                                   class="cc-input">
                            @error('poc_email')
                                <p class="cc-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="cc-field">
                            <label for="poc_telefono">
                                Teléfono del POC
                            </label>
                            <input type="text"
                                   name="poc_telefono"
                                   id="poc_telefono"
                                   value="{{ old('poc_telefono') }}"
                                   maxlength="9"
                                   inputmode="numeric"
                                   autocomplete="off"
                                   class="cc-input">
                            @error('poc_telefono')
                                <p class="cc-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="cc-actions">
                        <button type="submit" class="cc-btn-primary">
                            Guardar empresa
                        </button>

                        <a href="{{ route('empresas.index') }}" class="cc-btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
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

        function formatPhone(value) {
            const digits = value.replace(/\D/g, '').slice(0, 8);

            if (digits.length <= 4) {
                return digits;
            }

            return `${digits.slice(0, 4)}-${digits.slice(4)}`;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const nitInput = document.getElementById('nit');
            const telefonoEmpresaInput = document.getElementById('telefono_empresa');
            const pocTelefonoInput = document.getElementById('poc_telefono');

            if (nitInput) {
                nitInput.addEventListener('input', function () {
                    this.value = formatNit(this.value);
                });
            }

            if (telefonoEmpresaInput) {
                telefonoEmpresaInput.addEventListener('input', function () {
                    this.value = formatPhone(this.value);
                });
            }

            if (pocTelefonoInput) {
                pocTelefonoInput.addEventListener('input', function () {
                    this.value = formatPhone(this.value);
                });
            }
        });
    </script>
</x-app-layout>