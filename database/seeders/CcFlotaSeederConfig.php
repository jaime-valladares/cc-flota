<?php

namespace Database\Seeders;

final class CcFlotaSeederConfig
{
    /**
     * Cantidad total de empresas que formarán parte
     * de la base demostrativa integral.
     */
    public const TOTAL_EMPRESAS = 18;

    /**
     * Cantidad aproximada de unidades.
     */
    public const TOTAL_UNIDADES = 180;

    /**
     * Cantidad aproximada de motoristas.
     */
    public const TOTAL_MOTORISTAS = 90;

    /**
     * Cantidad aproximada de gasolineras internas.
     */
    public const TOTAL_GASOLINERAS_INTERNAS = 30;

    /**
     * Cantidad aproximada de gasolineras externas.
     */
    public const TOTAL_GASOLINERAS_EXTERNAS = 45;

    /**
     * Cantidad aproximada de puntos de ruta.
     */
    public const TOTAL_PUNTOS_RUTA = 140;

    /**
     * Cantidad aproximada de rutas.
     */
    public const TOTAL_RUTAS = 220;

    /**
     * Cantidad objetivo de abastecimientos históricos.
     */
    public const TOTAL_ABASTECIMIENTOS_OBJETIVO = 3600;

    /**
     * Cantidad objetivo de recargas de tanques.
     */
    public const TOTAL_RECARGAS_OBJETIVO = 720;

    /**
     * Semilla determinística.
     *
     * Toda generación pseudoaleatoria deberá partir
     * de este valor para producir siempre la misma base.
     */
    public const RANDOM_SEED = 260721;

    /**
     * Inicio correlativo para marchamos.
     *
     * Debe producir códigos de exactamente 7 dígitos.
     */
    public const MARCHAMO_INICIO = 1000000;

    /**
     * Inicio correlativo para licencias de motoristas.
     */
    public const LICENCIA_MOTORISTA_INICIO = 500000;

    /**
     * Inicio correlativo para placas.
     */
    public const PLACA_INICIO = 1000;

    /**
     * Perfiles de empresa.
     *
     * La suma de cantidades debe coincidir con
     * TOTAL_EMPRESAS.
     */
    public const PERFILES_EMPRESA = [
        'operacion_estable' => [
            'cantidad' => 5,
            'descripcion' =>
                'Operación regular, inventario saludable '
                . 'y rendimiento dentro de parámetros.',
        ],

        'alto_consumo' => [
            'cantidad' => 3,
            'descripcion' =>
                'Mayor frecuencia de abastecimientos, '
                . 'recargas e intensidad operacional.',
        ],

        'dependencia_externa' => [
            'cantidad' => 2,
            'descripcion' =>
                'Predominio de abastecimientos desde '
                . 'gasolineras externas.',
        ],

        'alertas_inventario' => [
            'cantidad' => 2,
            'descripcion' =>
                'Tanques cercanos o por debajo del mínimo.',
        ],

        'incidencias_licencias' => [
            'cantidad' => 2,
            'descripcion' =>
                'Licencias vigentes, vencidas, futuras '
                . 'e inactivas.',
        ],

        'incidencias_marchamos' => [
            'cantidad' => 2,
            'descripcion' =>
                'Reemplazos, intervenciones posteriores '
                . 'y bloqueos administrativos.',
        ],

        'historicos_inactivos' => [
            'cantidad' => 1,
            'descripcion' =>
                'Empresa y elementos históricos inactivos.',
        ],

        'auditoria_compleja' => [
            'cantidad' => 1,
            'descripcion' =>
                'Anulaciones, correcciones y trazabilidad '
                . 'operativa avanzada.',
        ],
    ];

    /**
     * Distribución de unidades según modelo de medición.
     *
     * La suma debe ser 100.
     */
    public const DISTRIBUCION_MODELOS = [
        'galones_kilometro' => 40,
        'galones_hora' => 30,
        'galones_viaje' => 30,
    ];

    /**
     * Distribución de plantillas de puntos de seguridad.
     *
     * La suma debe ser 100.
     */
    public const DISTRIBUCION_PLANTILLAS = [
        'plantilla_1_tanque' => 35,
        'plantilla_2_tanques' => 50,
        'plantilla_3_tanques' => 15,
    ];

    /**
     * Distribución general de orígenes de combustible.
     *
     * La suma debe ser 100.
     */
    public const DISTRIBUCION_ORIGEN = [
        'interno' => 70,
        'externo' => 30,
    ];

    /**
     * Distribución de estados de empresas.
     */
    public const DISTRIBUCION_ESTADOS_EMPRESA = [
        'activa' => 94,
        'inactiva' => 6,
    ];

    /**
     * Distribución de estados de unidades.
     */
    public const DISTRIBUCION_ESTADOS_UNIDAD = [
        'activa' => 88,
        'inactiva' => 7,
        'registrada' => 5,
    ];

    /**
     * Distribución de estados de licencias.
     */
    public const DISTRIBUCION_LICENCIAS = [
        'vigente' => 82,
        'vencida' => 8,
        'futura' => 4,
        'inactiva' => 4,
        'sin_licencia' => 2,
    ];

    /**
     * Distribución de estados de motoristas.
     */
    public const DISTRIBUCION_MOTORISTAS = [
        'activo' => 90,
        'inactivo' => 10,
    ];

    /**
     * Distribución de condición de tanques.
     */
    public const DISTRIBUCION_TANQUES = [
        'normal' => 65,
        'cercano_minimo' => 15,
        'bajo_minimo' => 12,
        'agotado' => 3,
        'inactivo' => 5,
    ];

    /**
     * Distribución de rendimientos esperados.
     */
    public const DISTRIBUCION_RENDIMIENTO = [
        'normal' => 70,
        'alto' => 10,
        'bajo' => 10,
        'sin_calculo' => 5,
        'discrepancia' => 5,
    ];

    /**
     * Cantidad mínima y máxima de abastecimientos
     * históricos por unidad operativa.
     */
    public const ABASTECIMIENTOS_POR_UNIDAD = [
        'minimo' => 8,
        'maximo' => 32,
    ];

    /**
     * Cantidad mínima y máxima de recargas por
     * gasolinera interna.
     */
    public const RECARGAS_POR_GASOLINERA = [
        'minimo' => 12,
        'maximo' => 36,
    ];

    /**
     * Rango de precios externos históricos.
     */
    public const PRECIOS_EXTERNOS = [
        'minimo' => 3.45,
        'maximo' => 5.25,
    ];

    /**
     * Rango de precios internos de compra histórica.
     */
    public const PRECIOS_INTERNOS = [
        'minimo' => 3.20,
        'maximo' => 4.95,
    ];

    /**
     * Capacidades comunes para unidades.
     */
    public const CAPACIDADES_UNIDAD = [
        60.00,
        80.00,
        100.00,
        120.00,
        150.00,
        180.00,
        200.00,
        240.00,
        300.00,
    ];

    /**
     * Capacidades comunes para tanques internos.
     */
    public const CAPACIDADES_TANQUE = [
        1000.00,
        1500.00,
        2000.00,
        2500.00,
        3000.00,
        5000.00,
    ];

    /**
     * Porcentajes de inventario mínimo.
     */
    public const PORCENTAJES_MINIMO_TANQUE = [
        10,
        15,
        20,
        25,
    ];

    /**
     * Marcas de unidades para variedad analítica.
     */
    public const MARCAS_UNIDAD = [
        'Freightliner',
        'International',
        'Kenworth',
        'Mack',
        'Peterbilt',
        'Volvo',
        'Hino',
        'Isuzu',
        'Mercedes-Benz',
        'Scania',
    ];

    /**
     * Compañías de gasolineras externas.
     */
    public const COMPANIAS_EXTERNAS = [
        'DLC',
        'Puma',
        'Texaco',
        'Uno',
        'Shell',
    ];

    /**
     * Motivos válidos para reemplazos generales.
     */
    public const MOTIVOS_REEMPLAZO_MARCHAMO = [
        'Daño',
        'Desgaste',
        'Pérdida',
        'Manipulación detectada',
        'Corrección de instalación',
    ];

    /**
     * Determina la cantidad total declarada
     * en los perfiles empresariales.
     */
    public static function totalEmpresasPerfiles(): int
    {
        return array_sum(
            array_column(
                self::PERFILES_EMPRESA,
                'cantidad'
            )
        );
    }

    /**
     * Verifica que una distribución porcentual
     * totalice exactamente 100.
     */
    public static function distribucionValida(
        array $distribucion
    ): bool {
        return array_sum($distribucion) === 100;
    }

    /**
     * Ejecuta validaciones estructurales básicas.
     */
    public static function validar(): array
    {
        $errores = [];

        if (
            self::totalEmpresasPerfiles()
            !== self::TOTAL_EMPRESAS
        ) {
            $errores[] =
                'La suma de perfiles empresariales no coincide '
                . 'con TOTAL_EMPRESAS.';
        }

        $distribuciones = [
            'modelos' =>
                self::DISTRIBUCION_MODELOS,

            'plantillas' =>
                self::DISTRIBUCION_PLANTILLAS,

            'origen' =>
                self::DISTRIBUCION_ORIGEN,

            'estados_empresa' =>
                self::DISTRIBUCION_ESTADOS_EMPRESA,

            'estados_unidad' =>
                self::DISTRIBUCION_ESTADOS_UNIDAD,

            'licencias' =>
                self::DISTRIBUCION_LICENCIAS,

            'motoristas' =>
                self::DISTRIBUCION_MOTORISTAS,

            'tanques' =>
                self::DISTRIBUCION_TANQUES,

            'rendimiento' =>
                self::DISTRIBUCION_RENDIMIENTO,
        ];

        foreach (
            $distribuciones
            as $nombre => $distribucion
        ) {
            if (
                ! self::distribucionValida(
                    $distribucion
                )
            ) {
                $errores[] =
                    "La distribución [{$nombre}] "
                    . 'no suma 100.';
            }
        }

        if (
            self::ABASTECIMIENTOS_POR_UNIDAD['minimo']
            > self::ABASTECIMIENTOS_POR_UNIDAD['maximo']
        ) {
            $errores[] =
                'El mínimo de abastecimientos por unidad '
                . 'no puede superar el máximo.';
        }

        if (
            self::RECARGAS_POR_GASOLINERA['minimo']
            > self::RECARGAS_POR_GASOLINERA['maximo']
        ) {
            $errores[] =
                'El mínimo de recargas por gasolinera '
                . 'no puede superar el máximo.';
        }

        return $errores;
    }
}