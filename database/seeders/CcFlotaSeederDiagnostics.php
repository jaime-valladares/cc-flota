<?php

namespace Database\Seeders;

use App\Models\Abastecimiento;
use App\Models\AbastecimientoRuta;
use App\Models\AbastecimientoTanque;
use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\GasolineraExterna;
use App\Models\Licencia;
use App\Models\Marchamo;
use App\Models\Motorista;
use App\Models\MovimientoInventarioCombustible;
use App\Models\PuntoRuta;
use App\Models\PuntoSeguridadUnidad;
use App\Models\RecargaCombustible;
use App\Models\ReemplazoMarchamoDetalle;
use App\Models\ReemplazoMarchamoEvento;
use App\Models\Role;
use App\Models\Ruta;
use App\Models\Tanque;
use App\Models\Unidad;
use App\Models\User;
use App\Support\PlantillasPuntosSeguridad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use RuntimeException;
use Throwable;

final class CcFlotaSeederDiagnostics
{
    /**
     * Ejecuta un diagnóstico ampliado completamente no destructivo.
     *
     * No crea, actualiza, elimina ni trunca registros.
     */
    public static function ejecutar(): array
    {
        $errores = [];
        $advertencias = [];
        $comprobaciones = [];

        self::validarConexion(
            errores: $errores,
            comprobaciones: $comprobaciones
        );

        self::validarSuperusuario(
            errores: $errores,
            advertencias: $advertencias,
            comprobaciones: $comprobaciones
        );

        self::validarReset(
            errores: $errores,
            advertencias: $advertencias,
            comprobaciones: $comprobaciones
        );

        self::validarModelosYFillable(
            errores: $errores,
            advertencias: $advertencias,
            comprobaciones: $comprobaciones
        );

        self::validarRelaciones(
            errores: $errores,
            comprobaciones: $comprobaciones
        );

        self::validarConstantesFuncionales(
            errores: $errores,
            comprobaciones: $comprobaciones
        );

        self::validarPlantillas(
            errores: $errores,
            advertencias: $advertencias,
            comprobaciones: $comprobaciones
        );

        self::validarMatematica(
            errores: $errores,
            advertencias: $advertencias,
            comprobaciones: $comprobaciones
        );

        self::validarEsquemaCritico(
            errores: $errores,
            comprobaciones: $comprobaciones
        );

        $resultado = [
            'estado' =>
                $errores === []
                    ? (
                        $advertencias === []
                            ? 'correcto'
                            : 'correcto_con_advertencias'
                    )
                    : 'bloqueado',

            'comprobaciones_superadas' =>
                count($comprobaciones),

            'errores' =>
                $errores,

            'advertencias' =>
                $advertencias,

            'comprobaciones' =>
                $comprobaciones,
        ];

        if ($errores !== []) {
            throw new RuntimeException(
                "El diagnóstico ampliado encontró "
                . count($errores)
                . " error(es) bloqueantes:\n- "
                . implode("\n- ", $errores)
                . (
                    $advertencias !== []
                        ? "\n\nAdvertencias adicionales:\n- "
                            . implode(
                                "\n- ",
                                $advertencias
                            )
                        : ''
                )
            );
        }

        return $resultado;
    }

    /**
     * Confirma el motor y la conexión actual.
     */
    private static function validarConexion(
        array &$errores,
        array &$comprobaciones
    ): void {
        try {
            $driver = DB::getDriverName();
            $base = DB::getDatabaseName();

            if ($driver !== 'mysql') {
                $errores[] =
                    "La conexión activa usa [{$driver}], "
                    . 'pero el reset requiere MySQL.';
            } else {
                $comprobaciones[] =
                    "Conexión MySQL activa: {$base}.";
            }
        } catch (Throwable $error) {
            $errores[] =
                'No fue posible validar la conexión: '
                . $error->getMessage();
        }
    }

    /**
     * Confirma que el usuario que debe preservarse existe
     * exactamente bajo las reglas acordadas.
     */
    private static function validarSuperusuario(
        array &$errores,
        array &$advertencias,
        array &$comprobaciones
    ): void {
        try {
            $cantidadUsuarios =
                User::query()->count();

            if ($cantidadUsuarios !== 1) {
                $errores[] =
                    'Se esperaba exactamente 1 usuario antes '
                    . "del reset y existen {$cantidadUsuarios}.";
            }

            $usuario = User::query()
                ->with('role')
                ->first();

            if (! $usuario) {
                $errores[] =
                    'No existe el superusuario que debe preservarse.';

                return;
            }

            $condiciones = [
                'email' =>
                    $usuario->email
                    === 'admin@cc-flota.local',

                'estado' =>
                    $usuario->estado
                    === 'activo',

                'empresa_id' =>
                    is_null(
                        $usuario->empresa_id
                    ),

                'tipo_usuario' =>
                    $usuario->tipo_usuario
                    === 'diesel_cop',

                'rol' =>
                    $usuario->role?->codigo
                    === 'DIESEL_SUPER_ADMIN',
            ];

            foreach (
                $condiciones
                as $campo => $correcto
            ) {
                if (! $correcto) {
                    $errores[] =
                        "El superusuario no cumple la condición "
                        . "[{$campo}].";
                }
            }

            if (
                $usuario->id !== 1
                && $usuario->email
                    === 'admin@cc-flota.local'
            ) {
                $advertencias[] =
                    'El superusuario es válido, pero su ID no es 1. '
                    . 'Los seeders usan el ID recuperado dinámicamente, '
                    . 'por lo que no es bloqueante.';
            }

            if (
                ! in_array(
                    'rol_id',
                    $usuario->getFillable(),
                    true
                )
            ) {
                $errores[] =
                    'El modelo User no permite asignar rol_id.';
            }

            $comprobaciones[] =
                'Superusuario único y preservable validado.';
        } catch (Throwable $error) {
            $errores[] =
                'No fue posible validar el superusuario: '
                . $error->getMessage();
        }
    }

    /**
     * Inspecciona el seeder de reset sin ejecutarlo.
     */
    private static function validarReset(
        array &$errores,
        array &$advertencias,
        array &$comprobaciones
    ): void {
        try {
            $reflexion =
                new ReflectionClass(
                    ResetCcFlotaDataSeeder::class
                );

            if (
                ! $reflexion->hasConstant(
                    'TABLAS_A_LIMPIAR'
                )
            ) {
                $errores[] =
                    'ResetCcFlotaDataSeeder no contiene '
                    . 'la constante TABLAS_A_LIMPIAR.';

                return;
            }

            $tablas =
                $reflexion->getConstant(
                    'TABLAS_A_LIMPIAR'
                );

            if (! is_array($tablas)) {
                $errores[] =
                    'TABLAS_A_LIMPIAR no es un arreglo.';

                return;
            }

            $protegidas = [
                'users',
                'roles',
                'permisos',
                'rol_permisos',
                'migrations',
                'password_reset_tokens',
                'sessions',
            ];

            $protegidasIncluidas =
                array_values(
                    array_intersect(
                        $protegidas,
                        $tablas
                    )
                );

            if ($protegidasIncluidas !== []) {
                $errores[] =
                    'El reset intenta limpiar tablas protegidas: '
                    . implode(
                        ', ',
                        $protegidasIncluidas
                    )
                    . '.';
            }

            $duplicadas =
                array_keys(
                    array_filter(
                        array_count_values($tablas),
                        fn (int $cantidad): bool =>
                            $cantidad > 1
                    )
                );

            if ($duplicadas !== []) {
                $advertencias[] =
                    'TABLAS_A_LIMPIAR contiene duplicados: '
                    . implode(', ', $duplicadas)
                    . '.';
            }

            foreach (
                $tablas
                as $tabla
            ) {
                if (! Schema::hasTable($tabla)) {
                    $errores[] =
                        "El reset espera la tabla [{$tabla}], "
                        . 'pero no existe.';
                }
            }

            $comprobaciones[] =
                'Lista de tablas del reset validada sin ejecutar truncados.';
        } catch (Throwable $error) {
            $errores[] =
                'No fue posible inspeccionar el reset: '
                . $error->getMessage();
        }
    }

    /**
     * Verifica los campos que cada archivo asigna mediante create,
     * fill o constructores Eloquent.
     */
    private static function validarModelosYFillable(
        array &$errores,
        array &$advertencias,
        array &$comprobaciones
    ): void {
        $requeridos = [
            Empresa::class => [
                'nombre_legal',
                'nombre_comercial',
                'nit',
                'direccion',
                'telefono_empresa',
                'correo_empresa',
                'poc_nombre',
                'poc_email',
                'poc_telefono',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            Unidad::class => [
                'empresa_id',
                'placa',
                'marca',
                'total_tanques',
                'cantidad_tanques_con_licencia',
                'capacidad_total',
                'capacidad_cubierta',
                'modelo_medicion',
                'estado',
                'creado_por',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            Licencia::class => [
                'empresa_id',
                'unidad_id',
                'periodo_vigencia_meses',
                'fecha_activacion',
                'fecha_vencimiento',
                'estado',
                'plantilla_puntos_seguridad',
                'creado_por',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            PuntoSeguridadUnidad::class => [
                'unidad_id',
                'orden',
                'codigo_punto',
                'grupo',
                'subgrupo',
                'nombre_punto',
                'descripcion',
                'posicion_tanque',
                'tipo_punto',
                'requiere_marchamo',
                'plantilla_origen',
                'criterio_origen',
                'estado_asignacion',
                'marchamo_actual_id',
                'estado',
                'creado_por',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            Marchamo::class => [
                'empresa_id',
                'unidad_id',
                'punto_seguridad_id',
                'codigo_marchamo',
                'fecha_activacion',
                'estado',
                'activo_actual',
                'fecha_desactivacion',
                'motivo_desactivacion',
                'origen_creacion',
                'creado_por',
                'actualizado_por',
            ],

            Motorista::class => [
                'empresa_id',
                'nombres',
                'apellidos',
                'licencia',
                'telefono',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            Gasolinera::class => [
                'empresa_id',
                'nombre',
                'direccion',
                'encargado',
                'telefono',
                'correo',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            Tanque::class => [
                'gasolinera_id',
                'nombre',
                'capacidad_total',
                'volumen_actual',
                'volumen_minimo_alerta',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            GasolineraExterna::class => [
                'empresa_id',
                'direccion',
                'compania',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            PuntoRuta::class => [
                'empresa_id',
                'nombre',
                'direccion',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            Ruta::class => [
                'empresa_id',
                'punto_origen_id',
                'punto_destino_id',
                'ruta',
                'kilometros_estimados',
                'galones_estimados',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            RecargaCombustible::class => [
                'empresa_id',
                'gasolinera_id',
                'precio_galon',
                'total_galones',
                'total_compra',
                'fecha_hora_recarga',
                'observaciones',
                'usuario_registra_id',
                'estado',
                'fecha_creacion',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_anulacion',
                'anulado_por',
                'motivo_anulacion',
            ],

            MovimientoInventarioCombustible::class => [
                'empresa_id',
                'tanque_id',
                'abastecimiento_id',
                'recarga_combustible_id',
                'tipo_movimiento',
                'volumen_anterior',
                'sentido_movimiento',
                'volumen_movimiento',
                'volumen_resultante',
                'subtotal_compra',
                'fecha_hora_movimiento',
                'observaciones',
                'usuario_registra_id',
                'estado',
                'fecha_creacion',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_anulacion',
                'anulado_por',
                'motivo_anulacion',
            ],

            Abastecimiento::class => [
                'empresa_id',
                'unidad_id',
                'motorista_id',
                'abastecimiento_anterior_id',
                'registrado_por',
                'empresa_nombre_snapshot',
                'unidad_placa_snapshot',
                'unidad_marca_snapshot',
                'unidad_modelo_snapshot',
                'motorista_nombre_snapshot',
                'motorista_licencia_snapshot',
                'fecha_hora_abastecimiento',
                'estado',
                'modelo_medicion',
                'lectura_actual',
                'lectura_anterior',
                'diferencia_lectura',
                'kilometraje_actual',
                'kilometraje_anterior',
                'diferencia_kilometraje',
                'horometro_actual',
                'horometro_anterior',
                'diferencia_horometro',
                'volumen_inicial',
                'volumen_cargado',
                'volumen_final',
                'capacidad_cubierta_snapshot',
                'volumen_final_anterior',
                'combustible_consumido_ciclo',
                'combustible_adicional_no_explicado',
                'tipo_origen',
                'gasolinera_interna_id',
                'gasolinera_externa_id',
                'origen_nombre_snapshot',
                'precio_galon',
                'total_pagado',
                'moneda',
                'total_rutas',
                'kilometros_teoricos',
                'galones_teoricos',
                'galones_por_kilometro',
                'kilometros_por_galon',
                'galones_por_hora',
                'diferencia_kilometros_teoricos',
                'diferencia_galones_teoricos',
                'total_tapones_abiertos',
                'total_marchamos_reemplazados',
                'fecha_anulacion',
                'anulado_por',
                'motivo_anulacion',
            ],

            AbastecimientoTanque::class => [
                'abastecimiento_id',
                'tanque_id',
                'orden',
                'tanque_nombre_snapshot',
                'capacidad_total_snapshot',
                'volumen_minimo_alerta_snapshot',
                'inventario_anterior',
                'galones_retirados',
                'inventario_resultante',
                'quedo_bajo_minimo',
            ],

            AbastecimientoRuta::class => [
                'abastecimiento_id',
                'ruta_id',
                'orden',
                'tipo_recorrido',
                'factor_recorrido',
                'ruta_nombre_snapshot',
                'punto_origen_id',
                'punto_destino_id',
                'punto_origen_nombre_snapshot',
                'punto_destino_nombre_snapshot',
                'kilometros_base_snapshot',
                'galones_base_snapshot',
                'kilometros_aplicados',
                'galones_aplicados',
            ],

            ReemplazoMarchamoEvento::class => [
                'empresa_id',
                'unidad_id',
                'abastecimiento_id',
                'motivo_reemplazo',
                'cantidad_reemplazos',
                'origen_evento',
                'estado',
                'fecha_registro',
                'registrado_por',
                'fecha_anulacion',
                'anulado_por',
                'motivo_anulacion',
            ],

            ReemplazoMarchamoDetalle::class => [
                'reemplazo_evento_id',
                'punto_seguridad_id',
                'marchamo_anterior_id',
                'marchamo_nuevo_id',
                'fecha_registro',
            ],
        ];

        foreach (
            $requeridos
            as $clase => $campos
        ) {
            /** @var Model $modelo */
            $modelo = new $clase();

            $fillable =
                $modelo->getFillable();

            $faltantes =
                array_values(
                    array_diff(
                        $campos,
                        $fillable
                    )
                );

            if ($faltantes !== []) {
                $errores[] =
                    class_basename($clase)
                    . ' no permite asignar: '
                    . implode(', ', $faltantes)
                    . '.';
            }

            if (
                $modelo->getGuarded() === ['*']
                && $fillable === []
            ) {
                $advertencias[] =
                    class_basename($clase)
                    . ' bloquea asignación masiva completa.';
            }
        }

        $comprobaciones[] =
            'Campos fillable de modelos operativos validados.';
    }

    /**
     * Confirma que las relaciones invocadas existen y devuelven
     * objetos Relation.
     */
    private static function validarRelaciones(
        array &$errores,
        array &$comprobaciones
    ): void {
        $relaciones = [
            User::class => [
                'role',
            ],

            Ruta::class => [
                'puntoOrigen',
                'puntoDestino',
            ],

            ReemplazoMarchamoDetalle::class => [
                'evento',
            ],
        ];

        foreach (
            $relaciones
            as $clase => $metodos
        ) {
            $modelo = new $clase();

            foreach (
                $metodos
                as $metodo
            ) {
                if (! method_exists($modelo, $metodo)) {
                    $errores[] =
                        class_basename($clase)
                        . " no contiene la relación [{$metodo}].";

                    continue;
                }

                try {
                    $relacion =
                        $modelo->{$metodo}();

                    if (! $relacion instanceof Relation) {
                        $errores[] =
                            class_basename($clase)
                            . "::{$metodo}() no devuelve "
                            . 'una relación Eloquent.';
                    }
                } catch (Throwable $error) {
                    $errores[] =
                        'No fue posible construir '
                        . class_basename($clase)
                        . "::{$metodo}(): "
                        . $error->getMessage();
                }
            }
        }

        $comprobaciones[] =
            'Relaciones Eloquent utilizadas por el seeder validadas.';
    }

    /**
     * Verifica literales críticos contra las constantes reales.
     */
    private static function validarConstantesFuncionales(
        array &$errores,
        array &$comprobaciones
    ): void {
        $esperadas = [
            Abastecimiento::ESTADO_REGISTRADO =>
                'registrado',

            Abastecimiento::ESTADO_ANULADO =>
                'anulado',

            Abastecimiento::ORIGEN_INTERNO =>
                'interno',

            Abastecimiento::ORIGEN_EXTERNO =>
                'externo',

            Abastecimiento::
                MODELO_KILOMETROS_GALON =>
                'kilometros_galon',

            Abastecimiento::
                MODELO_GALONES_HORA =>
                'galones_hora',

            Abastecimiento::
                MODELO_GALONES_VIAJE =>
                'galones_viaje',

            AbastecimientoRuta::TIPO_IDA =>
                'ida',

            AbastecimientoRuta::TIPO_IDA_VUELTA =>
                'ida_vuelta',

            ReemplazoMarchamoEvento::
                MOTIVO_APERTURA_ABASTECIMIENTO =>
                'apertura_abastecimiento',

            ReemplazoMarchamoEvento::
                ORIGEN_ABASTECIMIENTO =>
                'abastecimiento',
        ];

        foreach (
            $esperadas
            as $real => $esperado
        ) {
            if ($real !== $esperado) {
                $errores[] =
                    "Una constante funcional devuelve [{$real}] "
                    . "y el materializador espera [{$esperado}].";
            }
        }

        $comprobaciones[] =
            'Constantes funcionales y literales operativos validados.';
    }

    /**
     * Revisa estructura, orden, códigos y tapones de las plantillas.
     */
    private static function validarPlantillas(
        array &$errores,
        array &$advertencias,
        array &$comprobaciones
    ): void {
        $plantillas = [
            'plantilla_1_tanque' => 1,
            'plantilla_2_tanques' => 2,
            'plantilla_3_tanques' => 3,
        ];

        $camposObligatorios = [
            'orden',
            'codigo_punto',
            'grupo',
            'subgrupo',
            'nombre_punto',
            'posicion_tanque',
            'tipo_punto',
            'requiere_marchamo',
            'criterio_origen',
        ];

        foreach (
            $plantillas
            as $nombre => $tanquesEsperados
        ) {
            try {
                $puntos =
                    PlantillasPuntosSeguridad::
                        porPlantilla($nombre);
            } catch (Throwable $error) {
                $errores[] =
                    "No fue posible cargar [{$nombre}]: "
                    . $error->getMessage();

                continue;
            }

            if ($puntos === []) {
                $errores[] =
                    "La plantilla [{$nombre}] está vacía.";

                continue;
            }

            foreach (
                $puntos
                as $indice => $punto
            ) {
                foreach (
                    $camposObligatorios
                    as $campo
                ) {
                    if (
                        ! array_key_exists(
                            $campo,
                            $punto
                        )
                    ) {
                        $errores[] =
                            "La plantilla [{$nombre}], punto "
                            . ($indice + 1)
                            . ", no contiene [{$campo}].";
                    }
                }
            }

            $ordenes =
                array_column(
                    $puntos,
                    'orden'
                );

            $ordenesDuplicados =
                self::duplicados($ordenes);

            if ($ordenesDuplicados !== []) {
                $errores[] =
                    "La plantilla [{$nombre}] contiene "
                    . 'órdenes duplicados: '
                    . implode(
                        ', ',
                        $ordenesDuplicados
                    )
                    . '.';
            }

            $ordenesEsperados =
                range(1, count($puntos));

            $ordenesOrdenados =
                $ordenes;

            sort($ordenesOrdenados);

            if (
                $ordenesOrdenados
                !== $ordenesEsperados
            ) {
                $errores[] =
                    "La plantilla [{$nombre}] no posee "
                    . 'una secuencia de orden continua.';
            }

            $codigos =
                array_column(
                    $puntos,
                    'codigo_punto'
                );

            $codigosDuplicados =
                self::duplicados($codigos);

            if ($codigosDuplicados !== []) {
                $advertencias[] =
                    "La plantilla [{$nombre}] contiene "
                    . 'códigos de punto repetidos: '
                    . implode(
                        ', ',
                        $codigosDuplicados
                    )
                    . '. La base permite el registro porque '
                    . 'la unicidad vigente es unidad_id + orden, '
                    . 'pero conviene revisar su significado funcional.';
            }

            $taponesDeposito =
                array_values(
                    array_filter(
                        $puntos,
                        fn (array $punto): bool =>
                            ($punto['tipo_punto'] ?? null)
                                === 'tapón'
                            && ($punto['subgrupo'] ?? null)
                                === 'Depósito'
                            && (bool) (
                                $punto[
                                    'requiere_marchamo'
                                ] ?? false
                            )
                    )
                );

            if (
                count($taponesDeposito)
                !== $tanquesEsperados
            ) {
                $errores[] =
                    "La plantilla [{$nombre}] debería "
                    . "tener {$tanquesEsperados} tapón(es) "
                    . 'de depósito y contiene '
                    . count($taponesDeposito)
                    . '.';
            }
        }

        $comprobaciones[] =
            'Plantillas y tapones de abastecimiento validados.';
    }

    /**
     * Revisa que las cantidades objetivo sean matemáticamente posibles.
     */
    private static function validarMatematica(
        array &$errores,
        array &$advertencias,
        array &$comprobaciones
    ): void {
        $totalEmpresas =
            CcFlotaSeederConfig::TOTAL_EMPRESAS;

        $totalUnidades =
            CcFlotaSeederConfig::TOTAL_UNIDADES;

        $totalMotoristas =
            CcFlotaSeederConfig::TOTAL_MOTORISTAS;

        if (
            $totalUnidades % $totalEmpresas !== 0
        ) {
            $errores[] =
                'TOTAL_UNIDADES no puede distribuirse '
                . 'uniformemente entre TOTAL_EMPRESAS.';
        }

        if (
            $totalMotoristas % $totalEmpresas !== 0
        ) {
            $errores[] =
                'TOTAL_MOTORISTAS no puede distribuirse '
                . 'uniformemente entre TOTAL_EMPRESAS.';
        }

        $minimo =
            CcFlotaSeederConfig::
                ABASTECIMIENTOS_POR_UNIDAD['minimo'];

        $maximo =
            CcFlotaSeederConfig::
                ABASTECIMIENTOS_POR_UNIDAD['maximo'];

        $objetivo =
            CcFlotaSeederConfig::
                TOTAL_ABASTECIMIENTOS_OBJETIVO;

        if ($minimo <= 0 || $maximo < $minimo) {
            $errores[] =
                'El rango ABASTECIMIENTOS_POR_UNIDAD '
                . 'no es válido.';
        }

        if (
            $objetivo
            < $totalUnidades * $minimo
        ) {
            $advertencias[] =
                'El objetivo no alcanzaría el mínimo para '
                . 'todas las unidades creadas. Esto solo es válido '
                . 'si parte de ellas queda deliberadamente no operable.';
        }

        if (
            $objetivo
            > $totalUnidades * $maximo
        ) {
            $errores[] =
                'El objetivo de abastecimientos supera '
                . 'el máximo posible aun usando todas las unidades.';
        }

        $distribuciones = [
            'DISTRIBUCION_MODELOS' =>
                CcFlotaSeederConfig::
                    DISTRIBUCION_MODELOS,

            'DISTRIBUCION_PLANTILLAS' =>
                CcFlotaSeederConfig::
                    DISTRIBUCION_PLANTILLAS,

            'DISTRIBUCION_ESTADOS_UNIDAD' =>
                CcFlotaSeederConfig::
                    DISTRIBUCION_ESTADOS_UNIDAD,

            'DISTRIBUCION_LICENCIAS' =>
                CcFlotaSeederConfig::
                    DISTRIBUCION_LICENCIAS,

            'DISTRIBUCION_MOTORISTAS' =>
                CcFlotaSeederConfig::
                    DISTRIBUCION_MOTORISTAS,

            'DISTRIBUCION_TANQUES' =>
                CcFlotaSeederConfig::
                    DISTRIBUCION_TANQUES,

            'DISTRIBUCION_ORIGEN' =>
                CcFlotaSeederConfig::
                    DISTRIBUCION_ORIGEN,
        ];

        foreach (
            $distribuciones
            as $nombre => $distribucion
        ) {
            if (
                ! CcFlotaSeederConfig::
                    distribucionValida(
                        $distribucion
                    )
            ) {
                $errores[] =
                    "{$nombre} no suma 100.";
            }
        }

        $comprobaciones[] =
            'Distribuciones y cantidades objetivo validadas.';
    }

    /**
     * Revisa índices únicos y tipos de columnas especialmente sensibles.
     */
    private static function validarEsquemaCritico(
        array &$errores,
        array &$comprobaciones
    ): void {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $base = DB::getDatabaseName();

        $indicesRequeridos = [
            [
                'tabla' =>
                    'unidades',

                'columnas' => [
                    'empresa_id',
                    'placa',
                ],
            ],
            [
                'tabla' =>
                    'marchamos',

                'columnas' => [
                    'codigo_marchamo',
                ],
            ],
            [
                'tabla' =>
                    'puntos_seguridad_unidad',

                'columnas' => [
                    'unidad_id',
                    'orden',
                ],
            ],
            [
                'tabla' =>
                    'licencias',

                'columnas' => [
                    'unidad_id',
                ],
            ],
        ];

        foreach (
            $indicesRequeridos
            as $requerido
        ) {
            $indices =
                DB::select(
                    'SELECT INDEX_NAME, COLUMN_NAME, '
                    . 'SEQ_IN_INDEX, NON_UNIQUE '
                    . 'FROM information_schema.STATISTICS '
                    . 'WHERE TABLE_SCHEMA = ? '
                    . 'AND TABLE_NAME = ? '
                    . 'ORDER BY INDEX_NAME, SEQ_IN_INDEX',
                    [
                        $base,
                        $requerido['tabla'],
                    ]
                );

            $agrupados = [];

            foreach (
                $indices
                as $indice
            ) {
                if (
                    (int) $indice->NON_UNIQUE
                    !== 0
                ) {
                    continue;
                }

                $agrupados[
                    $indice->INDEX_NAME
                ][] = $indice->COLUMN_NAME;
            }

            $encontrado = false;

            foreach (
                $agrupados
                as $columnas
            ) {
                if (
                    $columnas
                    === $requerido['columnas']
                ) {
                    $encontrado = true;
                    break;
                }
            }

            if (! $encontrado) {
                $errores[] =
                    'No se encontró el índice único esperado en '
                    . $requerido['tabla']
                    . ' ('
                    . implode(
                        ', ',
                        $requerido['columnas']
                    )
                    . ').';
            }
        }

        $nombresPlacasDuplicados = DB::table('unidades')
            ->select('empresa_id', 'placa')
            ->groupBy('empresa_id', 'placa')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($nombresPlacasDuplicados) {
            $errores[] =
                'Existen Nombres / Placas duplicados dentro de una empresa.';
        }

        $snapshot = DB::selectOne(
            'SELECT CHARACTER_MAXIMUM_LENGTH AS longitud '
            . 'FROM information_schema.COLUMNS '
            . 'WHERE TABLE_SCHEMA = ? '
            . 'AND TABLE_NAME = ? '
            . 'AND COLUMN_NAME = ?',
            [
                $base,
                'abastecimientos',
                'unidad_placa_snapshot',
            ]
        );

        if ((int) ($snapshot->longitud ?? 0) < 30) {
            $errores[] =
                'abastecimientos.unidad_placa_snapshot debe aceptar 30 caracteres.';
        }

        $comprobaciones[] =
            'Unicidad por empresa y longitud del snapshot validadas.';
    }

    /**
     * Devuelve valores repetidos conservando una sola aparición.
     */
    private static function duplicados(
        array $valores
    ): array {
        return array_values(
            array_keys(
                array_filter(
                    array_count_values(
                        array_map(
                            fn ($valor): string =>
                                (string) $valor,
                            $valores
                        )
                    ),
                    fn (int $cantidad): bool =>
                        $cantidad > 1
                )
            )
        );
    }
}
