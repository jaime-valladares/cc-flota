<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Reemplazo de marchamos
                        </h3>
                        <p class="cc-subtitle">
                            Seleccione uno o varios puntos de seguridad e ingrese los nuevos códigos de marchamo.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('marchamos.reemplazos.index', [
                            'empresa_id' => $unidad->empresa_id,
                            'unidad_id' => $unidad->id,
                            'consultar' => 1,
                        ]) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="cc-alert-danger">
                        <strong>Revise la información ingresada.</strong>

                        <ul class="mt-2 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $totalPuntos = $puntos->count();
                    $totalMarchamosActuales = $puntos->filter(fn ($punto) => ! is_null($punto->marchamo_actual_id))->count();
                @endphp

                <section class="cc-detail-section">
                    <div class="cc-detail-section-header">
                        <h5>
                            Unidad seleccionada
                        </h5>
                        <p>
                            Esta unidad está activa y cuenta con cobertura completa. El reemplazo no desactiva la unidad.
                        </p>
                    </div>

                    <div class="cc-detail-grid">
                        <div class="cc-detail-item">
                            <div class="cc-detail-label">
                                Unidad
                            </div>
                            <div class="cc-detail-value">
                                {{ $unidad->placa }}
                                <span class="text-[var(--cc-text-muted)]">
                                    · {{ $unidad->marca ?: 'Sin marca' }}
                                </span>
                            </div>
                        </div>

                        <div class="cc-detail-item">
                            <div class="cc-detail-label">
                                Empresa
                            </div>
                            <div class="cc-detail-value">
                                @if ($unidad->empresa)
                                    {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                @else
                                    Sin empresa
                                @endif
                            </div>
                        </div>

                        <div class="cc-detail-item">
                            <div class="cc-detail-label">
                                Licencia
                            </div>
                            <div class="cc-detail-value">
                                @if ($unidad->licencia)
                                    {{ $unidad->licencia->periodo_vigencia_texto }}
                                    <span class="text-[var(--cc-text-muted)]">
                                        · {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                    </span>
                                @else
                                    Sin licencia
                                @endif
                            </div>
                        </div>

                        <div class="cc-detail-item">
                            <div class="cc-detail-label">
                                Cobertura
                            </div>
                            <div class="cc-detail-value">
                                {{ $totalMarchamosActuales }} / {{ $totalPuntos }}
                                <span class="text-[var(--cc-success)]">
                                    · Completa
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <form method="POST" action="{{ route('marchamos.reemplazos.store', $unidad) }}" class="mt-6">
                    @csrf

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Puntos de seguridad
                            </h5>
                            <p>
                                Marque los puntos que desea reemplazar. Cada punto seleccionado requiere un nuevo código de 7 dígitos y un motivo.
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="cc-table">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">Sel.</th>
                                        <th style="width: 90px;">Orden</th>
                                        <th>Punto de seguridad</th>
                                        <th style="width: 150px;">Marchamo actual</th>
                                        <th style="width: 220px;">Nuevo marchamo</th>
                                        <th style="width: 260px;">Motivo</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($puntos as $index => $punto)
                                        <tr>
                                            <td>
                                                <input
                                                    type="hidden"
                                                    name="reemplazos[{{ $index }}][punto_seguridad_id]"
                                                    value="{{ $punto->id }}">

                                                <input
                                                    type="checkbox"
                                                    name="reemplazos[{{ $index }}][seleccionado]"
                                                    value="1"
                                                    class="h-5 w-5 rounded border-[var(--cc-border)] text-[var(--cc-primary)] focus:ring-[var(--cc-primary)]"
                                                    @checked(old("reemplazos.{$index}.seleccionado"))>
                                            </td>

                                            <td>
                                                <div class="font-bold text-[var(--cc-text-main)]">
                                                    {{ $punto->orden }}
                                                </div>

                                                @if ($punto->codigo_punto)
                                                    <div class="text-xs text-[var(--cc-text-muted)]">
                                                        {{ $punto->codigo_punto }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="font-bold text-[var(--cc-text-main)]">
                                                    {{ $punto->nombre_punto }}
                                                </div>

                                                <div class="text-sm text-[var(--cc-text-muted)]">
                                                    {{ $punto->grupo ?: 'Sin grupo' }}

                                                    @if ($punto->subgrupo)
                                                        · {{ $punto->subgrupo }}
                                                    @endif

                                                    @if ($punto->posicion_tanque)
                                                        · {{ $punto->posicion_tanque }}
                                                    @endif
                                                </div>
                                            </td>

                                            <td>
                                                @if ($punto->marchamoActual)
                                                    <div class="font-bold text-[var(--cc-text-main)]">
                                                        {{ $punto->marchamoActual->codigo_marchamo }}
                                                    </div>

                                                    <div class="text-sm text-[var(--cc-success)]">
                                                        Activo
                                                    </div>
                                                @else
                                                    <span class="text-[var(--cc-text-muted)]">
                                                        Sin marchamo
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                <input
                                                    type="text"
                                                    name="reemplazos[{{ $index }}][nuevo_codigo_marchamo]"
                                                    value="{{ old("reemplazos.{$index}.nuevo_codigo_marchamo") }}"
                                                    class="cc-input"
                                                    placeholder="0000000"
                                                    maxlength="7"
                                                    inputmode="numeric">
                                            </td>

                                            <td>
                                                <select
                                                    name="reemplazos[{{ $index }}][motivo_reemplazo]"
                                                    class="cc-input">
                                                    <option value="">Seleccione motivo</option>

                                                    @foreach ($motivosReemplazo as $codigo => $texto)
                                                        <option value="{{ $codigo }}" @selected(old("reemplazos.{$index}.motivo_reemplazo") === $codigo)>
                                                            {{ $texto }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-[var(--cc-text-muted)] py-8">
                                                No hay puntos de seguridad disponibles para esta unidad.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="cc-form-actions mt-6">
                            <a href="{{ route('marchamos.reemplazos.index', [
                                'empresa_id' => $unidad->empresa_id,
                                'unidad_id' => $unidad->id,
                                'consultar' => 1,
                            ]) }}" class="cc-btn-secondary cc-btn-form-action">
                                Cancelar
                            </a>

                            <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                Confirmar reemplazos
                            </button>
                        </div>
                    </section>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>