@php
    $puntosExtra = $unidad->puntosSeguridad
        ->where('plantilla_origen', 'extra')
        ->values();
@endphp

<section class="cc-detail-section mt-6">
    <div class="cc-detail-section-header">
        <h5>Puntos de seguridad extra</h5>
        <p>
            Agregue hasta 10 puntos adicionales. Su definición puede corregirse
            únicamente antes de finalizar la asignación inicial.
        </p>
    </div>

    <div class="px-5 py-4">
        @if ($puntosExtra->isEmpty())
            <p class="text-sm text-[var(--cc-text-muted)] mb-4">
                Esta unidad todavía no tiene puntos de seguridad extra.
            </p>
        @else
            <div class="flex flex-col gap-3 mb-5">
                @foreach ($puntosExtra as $puntoExtra)
                    <div class="cc-card p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                            <form
                                method="POST"
                                action="{{ route(
                                    'marchamos.asignacion-inicial.extras.update',
                                    ['unidad' => $unidad, 'punto' => $puntoExtra]
                                ) }}"
                                class="flex-1"
                            >
                                @csrf
                                @method('PATCH')

                                <label for="extra_nombre_{{ $puntoExtra->id }}">
                                    Punto de Seguridad
                                </label>
                                <input
                                    id="extra_nombre_{{ $puntoExtra->id }}"
                                    name="nombre_punto"
                                    type="text"
                                    class="cc-input"
                                    value="{{ $puntoExtra->nombre_punto }}"
                                    maxlength="150"
                                    required
                                >

                                <div class="cc-table-adaptive-muted mt-1">
                                    {{ $puntoExtra->codigo_punto }} · Posición: Extra
                                </div>

                                <button type="submit" class="cc-btn-secondary mt-3">
                                    Guardar nombre
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route(
                                    'marchamos.asignacion-inicial.extras.destroy',
                                    ['unidad' => $unidad, 'punto' => $puntoExtra]
                                ) }}"
                                onsubmit="return confirm('¿Eliminar este punto extra y su marchamo provisional?');"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="cc-btn-danger">
                                    Eliminar extra
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($puntosExtra->count() < 10)
            <form
                method="POST"
                action="{{ route('marchamos.asignacion-inicial.extras.store', $unidad) }}"
                class="flex flex-col gap-3 lg:flex-row lg:items-end"
            >
                @csrf
                <div class="cc-field flex-1">
                    <label for="nuevo_extra_nombre">
                        Punto de Seguridad <span class="cc-required">*</span>
                    </label>
                    <input
                        id="nuevo_extra_nombre"
                        name="nombre_punto"
                        type="text"
                        class="cc-input"
                        value="{{ old('nombre_punto') }}"
                        maxlength="150"
                        placeholder="Ej. Bomba auxiliar"
                        required
                    >
                </div>

                <button type="submit" class="cc-btn-primary cc-btn-form-action">
                    Agregar marchamo extra
                </button>
            </form>
        @else
            <div class="cc-alert cc-alert-warning">
                La unidad alcanzó el máximo de 10 puntos extra.
            </div>
        @endif
    </div>
</section>
