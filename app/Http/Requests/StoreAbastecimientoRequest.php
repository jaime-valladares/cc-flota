<?php

namespace App\Http\Requests;

use App\Models\Abastecimiento;
use App\Models\Unidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbastecimientoRequest extends FormRequest
{
    /**
     * La autorización específica por empresa y operación
     * se valida nuevamente en el controlador y el servicio.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas básicas de estructura y formato.
     *
     * Las validaciones transaccionales, de concurrencia,
     * inventario, capacidad, relaciones y marchamos se
     * ejecutan nuevamente en AbastecimientoService.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Selección principal
            |--------------------------------------------------------------------------
            */

            'empresa_id' => [
                'required',
                'integer',
                'exists:empresas,id',
            ],

            'unidad_id' => [
                'required',
                'integer',
                'exists:unidades,id',
            ],

            'motorista_id' => [
                'required',
                'integer',
                'exists:motoristas,id',
            ],

            'ultimo_abastecimiento_id' => [
                'nullable',
                'integer',
                'exists:abastecimientos,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Kilometraje, horómetro y combustible
            |--------------------------------------------------------------------------
            */

            'kilometraje_actual' => [
                'required',
                'numeric',
                'gte:0',
                'decimal:0,2',
            ],

            'horometro_actual' => [
                'nullable',

                Rule::requiredIf(
                    fn (): bool =>
                        $this->modeloUnidad()
                        === Abastecimiento::MODELO_GALONES_HORA
                ),

                'numeric',
                'gte:0',
                'decimal:0,2',
            ],

            /*
            |--------------------------------------------------------------------------
            | Tipo de origen
            |--------------------------------------------------------------------------
            */

            'tipo_origen' => [
                'required',

                Rule::in([
                    Abastecimiento::ORIGEN_INTERNO,
                    Abastecimiento::ORIGEN_EXTERNO,
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Origen interno
            |--------------------------------------------------------------------------
            */

            'gasolinera_interna_id' => [
                'nullable',
                'required_if:tipo_origen,interno',
                'integer',
                'exists:gasolineras,id',
            ],

            'tanques' => [
                'nullable',
                'required_if:tipo_origen,interno',
                'array',
                'min:1',
            ],

            'tanques.*.tanque_id' => [
                'required_with:tanques',
                'integer',
                'distinct',
                'exists:tanques,id',
            ],

            'tanques.*.galones' => [
                'nullable',
                'numeric',
                'gte:0',
                'decimal:0,2',
            ],

            /*
            |--------------------------------------------------------------------------
            | Origen externo
            |--------------------------------------------------------------------------
            */

            'gasolinera_externa_id' => [
                'nullable',
                'required_if:tipo_origen,externo',
                'integer',
                'exists:gasolineras_externas,id',
            ],

            'galones_externos' => [
                'nullable',
                'required_if:tipo_origen,externo',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'precio_galon' => [
                'nullable',
                'required_if:tipo_origen,externo',
                'numeric',
                'gt:0',
                'decimal:0,4',
            ],

            /*
            |--------------------------------------------------------------------------
            | Rutas
            |--------------------------------------------------------------------------
            */

            'rutas' => [
                'nullable',
                'array',
            ],

            'rutas.*.ruta_id' => [
                'required_with:rutas',
                'integer',
                'exists:rutas,id',
            ],

            'rutas.*.tipo_recorrido' => [
                'required_with:rutas',

                Rule::in([
                    'ida',
                    'ida_vuelta',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Tapones y marchamos
            |--------------------------------------------------------------------------
            */

            'marchamos' => [
                'required',
                'array',
                'min:1',
            ],

            'marchamos.*.punto_seguridad_id' => [
                'required',
                'integer',
                'distinct',
                'exists:puntos_seguridad_unidad,id',
            ],

            'marchamos.*.nuevo_codigo_marchamo' => [
                'required',
                'string',
                'regex:/^\d{7}$/',
                'distinct',
            ],

            /*
            |--------------------------------------------------------------------------
            | Navegación
            |--------------------------------------------------------------------------
            */

            'return_to' => [
                'nullable',

                Rule::in([
                    'ventana',
                ]),
            ],
        ];
    }

    /**
     * Mensajes funcionales del formulario.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'empresa_id.required' =>
                'Debe seleccionar una empresa.',

            'empresa_id.exists' =>
                'La empresa seleccionada no existe.',

            'unidad_id.required' =>
                'Debe seleccionar una unidad.',

            'unidad_id.exists' =>
                'La unidad seleccionada no existe.',

            'motorista_id.required' =>
                'Debe seleccionar un motorista.',

            'motorista_id.exists' =>
                'El motorista seleccionado no existe.',

            'ultimo_abastecimiento_id.exists' =>
                'El abastecimiento anterior indicado ya no está disponible.',

            /*
            |--------------------------------------------------------------------------
            | Kilometraje
            |--------------------------------------------------------------------------
            */

            'kilometraje_actual.required' =>
                'Debe ingresar el kilometraje actual de la unidad.',

            'kilometraje_actual.numeric' =>
                'El kilometraje actual debe ser numérico.',

            'kilometraje_actual.gte' =>
                'El kilometraje actual no puede ser negativo.',

            'kilometraje_actual.decimal' =>
                'El kilometraje actual puede contener como máximo 2 decimales.',

            /*
            |--------------------------------------------------------------------------
            | Horómetro
            |--------------------------------------------------------------------------
            */

            'horometro_actual.required' =>
                'Debe ingresar la lectura actual del horómetro.',

            'horometro_actual.numeric' =>
                'La lectura del horómetro debe ser numérica.',

            'horometro_actual.gte' =>
                'La lectura del horómetro no puede ser negativa.',

            'horometro_actual.decimal' =>
                'La lectura del horómetro puede contener como máximo 2 decimales.',

            /*
            |--------------------------------------------------------------------------
            | Origen
            |--------------------------------------------------------------------------
            */

            'tipo_origen.required' =>
                'Debe seleccionar el origen del combustible.',

            'tipo_origen.in' =>
                'El origen del combustible seleccionado no es válido.',

            'gasolinera_interna_id.required_if' =>
                'Debe seleccionar una gasolinera interna.',

            'gasolinera_interna_id.exists' =>
                'La gasolinera interna seleccionada no existe.',

            'tanques.required_if' =>
                'Debe seleccionar al menos un tanque interno.',

            'tanques.array' =>
                'La distribución por tanque no tiene el formato esperado.',

            'tanques.min' =>
                'Debe seleccionar al menos un tanque interno.',

            'tanques.*.tanque_id.required_with' =>
                'Cada línea debe identificar un tanque.',

            'tanques.*.tanque_id.distinct' =>
                'No puede repetir el mismo tanque dentro del abastecimiento.',

            'tanques.*.tanque_id.exists' =>
                'Uno de los tanques seleccionados no existe.',

            'tanques.*.galones.numeric' =>
                'Los galones retirados deben ser numéricos.',

            'tanques.*.galones.gte' =>
                'Los galones retirados no pueden ser negativos.',

            'tanques.*.galones.decimal' =>
                'Los galones retirados pueden contener como máximo 2 decimales.',

            'gasolinera_externa_id.required_if' =>
                'Debe seleccionar una gasolinera externa.',

            'gasolinera_externa_id.exists' =>
                'La gasolinera externa seleccionada no existe.',

            'galones_externos.required_if' =>
                'Debe ingresar la cantidad de galones cargados.',

            'galones_externos.numeric' =>
                'Los galones cargados deben ser numéricos.',

            'galones_externos.gt' =>
                'Los galones cargados deben ser mayores que cero.',

            'galones_externos.decimal' =>
                'Los galones cargados pueden contener como máximo 2 decimales.',

            'precio_galon.required_if' =>
                'Debe ingresar el precio por galón.',

            'precio_galon.numeric' =>
                'El precio por galón debe ser numérico.',

            'precio_galon.gt' =>
                'El precio por galón debe ser mayor que cero.',

            'precio_galon.decimal' =>
                'El precio por galón puede contener como máximo 4 decimales.',

            /*
            |--------------------------------------------------------------------------
            | Rutas
            |--------------------------------------------------------------------------
            */

            'rutas.array' =>
                'La información de rutas no tiene el formato esperado.',

            'rutas.*.ruta_id.required_with' =>
                'Cada recorrido debe tener una ruta seleccionada.',

            'rutas.*.ruta_id.exists' =>
                'Una de las rutas seleccionadas no existe.',

            'rutas.*.tipo_recorrido.required_with' =>
                'Debe indicar el tipo de recorrido de cada ruta.',

            'rutas.*.tipo_recorrido.in' =>
                'El tipo de recorrido seleccionado no es válido.',

            /*
            |--------------------------------------------------------------------------
            | Marchamos
            |--------------------------------------------------------------------------
            */

            'marchamos.required' =>
                'Debe seleccionar al menos un tapón abierto.',

            'marchamos.array' =>
                'La información de marchamos no tiene el formato esperado.',

            'marchamos.min' =>
                'Debe seleccionar al menos un tapón abierto.',

            'marchamos.*.punto_seguridad_id.required' =>
                'Cada reemplazo debe identificar un punto de seguridad.',

            'marchamos.*.punto_seguridad_id.distinct' =>
                'No puede repetir el mismo punto de seguridad.',

            'marchamos.*.punto_seguridad_id.exists' =>
                'Uno de los puntos de seguridad seleccionados no existe.',

            'marchamos.*.nuevo_codigo_marchamo.required' =>
                'Ingrese el nuevo código de cada marchamo seleccionado.',

            'marchamos.*.nuevo_codigo_marchamo.regex' =>
                'Cada nuevo código de marchamo debe contener exactamente 7 dígitos.',

            'marchamos.*.nuevo_codigo_marchamo.distinct' =>
                'No puede repetir un código de marchamo dentro de la operación.',

            'return_to.in' =>
                'El destino de retorno no es válido.',
        ];
    }

    /**
     * Normaliza valores antes de ejecutar la validación.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tipo_origen' =>
                trim(
                    (string) $this->input(
                        'tipo_origen',
                        ''
                    )
                ),

            'kilometraje_actual' =>
                $this->normalizarNumero(
                    $this->input(
                        'kilometraje_actual'
                    )
                ),

            'horometro_actual' =>
                $this->normalizarNumero(
                    $this->input(
                        'horometro_actual'
                    )
                ),

            'galones_externos' =>
                $this->normalizarNumero(
                    $this->input(
                        'galones_externos'
                    )
                ),

            'precio_galon' =>
                $this->normalizarNumero(
                    $this->input(
                        'precio_galon'
                    )
                ),
        ]);
    }

    /**
     * Resolver el modelo de medición de la unidad.
     */
    private function modeloUnidad(): ?string
    {
        $unidadRuta = $this->route('unidad');

        if ($unidadRuta instanceof Unidad) {
            return $unidadRuta->modelo_medicion;
        }

        $unidadId = (int) (
            $this->input('unidad_id')
            ?: $unidadRuta
            ?: 0
        );

        if ($unidadId <= 0) {
            return null;
        }

        return Unidad::query()
            ->whereKey($unidadId)
            ->value('modelo_medicion');
    }

    /**
     * Conserva null y elimina separadores o espacios comunes.
     */
    private function normalizarNumero(
        mixed $valor
    ): mixed {
        if (
            is_null($valor)
            || $valor === ''
        ) {
            return null;
        }

        if (! is_string($valor)) {
            return $valor;
        }

        return str_replace(
            [
                ',',
                ' ',
            ],
            '',
            trim($valor)
        );
    }
}
