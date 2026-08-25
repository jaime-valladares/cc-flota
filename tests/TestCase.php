<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private const BASE_DATOS_TESTING = 'cc_flota_testing';

    private const BASE_DATOS_DESARROLLO = 'cc_flota';

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $entorno = $app->environment();
        $conexionConfigurada = (string) config('database.default');
        $baseConfigurada = (string) config(
            "database.connections.{$conexionConfigurada}.database"
        );

        if (
            $entorno !== 'testing'
            || $conexionConfigurada !== 'mysql'
            || $baseConfigurada !== self::BASE_DATOS_TESTING
            || $baseConfigurada === self::BASE_DATOS_DESARROLLO
        ) {
            throw $this->excepcionBaseNoAutorizada(
                $entorno,
                $conexionConfigurada,
                $baseConfigurada
            );
        }

        $conexion = $app->make('db')->connection();
        $baseActiva = (string) (
            $conexion->selectOne(
                'SELECT DATABASE() AS base_activa'
            )->base_activa
            ?? ''
        );

        if (
            $conexion->getDriverName() !== 'mysql'
            || $baseActiva !== self::BASE_DATOS_TESTING
            || $baseActiva === self::BASE_DATOS_DESARROLLO
        ) {
            throw $this->excepcionBaseNoAutorizada(
                $entorno,
                $conexion->getDriverName(),
                $baseActiva
            );
        }

        return $app;
    }

    private function excepcionBaseNoAutorizada(
        string $entorno,
        string $conexion,
        string $base
    ): RuntimeException {
        return new RuntimeException(
            'Operación de testing potencialmente destructiva bloqueada. '
            . 'Solo se autoriza APP_ENV=testing, conexión mysql y la base '
            . self::BASE_DATOS_TESTING
            . ". Valores detectados: entorno={$entorno}, "
            . "conexión={$conexion}, base={$base}."
        );
    }
}
