@props(['name', 'label', 'value' => null, 'max'])

<div class="cc-field" x-data="filtroMes" @click.outside="cerrar()" @keydown.escape="if (abierto) cerrar(true)">
    <label for="{{ $name }}" :for="'{{ $name }}-selector'">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="month" class="cc-input" x-ref="campo" x-show="false"
           value="{{ old($name, $value) }}" max="{{ $max }}">

    <div class="cc-filter-multiselect cc-month-picker" :class="{ 'is-open': abierto }" x-cloak>
        <button id="{{ $name }}-selector" type="button" class="cc-filter-multiselect-toggle cc-month-picker-toggle"
                x-ref="activador" @click="alternar()" :aria-expanded="abierto.toString()"
                aria-controls="{{ $name }}-meses">
            <span :class="{ 'cc-month-picker-placeholder': !valor }" x-text="texto"></span>
            <span class="cc-month-picker-icon" aria-hidden="true"></span>
        </button>

        <div id="{{ $name }}-meses" class="cc-filter-multiselect-menu cc-month-picker-menu"
             :class="{ 'is-open': abierto }" x-show="abierto" role="group" aria-label="{{ $label }}">
            <div class="cc-month-picker-header">
                <button type="button" class="cc-month-picker-option" @click="anio--" :disabled="anio <= 1"
                        aria-label="Año anterior">‹</button>
                <strong x-text="anio" aria-live="polite"></strong>
                <button type="button" class="cc-month-picker-option" @click="anio++" :disabled="anio >= anioMaximo"
                        aria-label="Año siguiente">›</button>
            </div>
            <div class="cc-month-picker-months">
                <template x-for="(mes, indice) in meses" :key="mes">
                    <button type="button" class="cc-month-picker-option" x-text="mes"
                            :disabled="esFuturo(indice + 1)"
                            :aria-pressed="(valor === fecha(indice + 1)).toString()"
                            :aria-label="mes + ' de ' + anio"
                            @click="seleccionar(fecha(indice + 1))"></button>
                </template>
            </div>
            <button type="button" class="cc-btn-secondary cc-month-picker-clear" :disabled="!valor"
                    @click="seleccionar('')">Quitar mes</button>
        </div>
    </div>

    @error($name)
        <div class="cc-error">{{ $message }}</div>
    @enderror
</div>
