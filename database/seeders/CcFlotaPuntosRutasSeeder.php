<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\PuntoRuta;
use App\Models\Ruta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CcFlotaPuntosRutasSeeder extends Seeder
{
    /**
     * Crea puntos de ruta y rutas bidireccionales.
     *
     * La combinación A-B representa también B-A; por eso nunca
     * se genera la combinación inversa como un registro separado.
     */
    public function run(): void
    {
        $this->command?->info(
            'Creando puntos de ruta y rutas...'
        );

        $usuarioId =
            CcFlotaSeederContext::superUserId();

        $empresas =
            CcFlotaSeederContext::referencia(
                'empresas.todas'
            );

        if ($empresas === []) {
            throw new RuntimeException(
                'No existen empresas registradas en el contexto.'
            );
        }

        $distribucionPuntos =
            $this->distribuirCantidad(
                cantidadGrupos: count($empresas),
                total: CcFlotaSeederConfig::
                    TOTAL_PUNTOS_RUTA
            );

        $distribucionRutas =
            $this->distribuirCantidad(
                cantidadGrupos: count($empresas),
                total: CcFlotaSeederConfig::
                    TOTAL_RUTAS
            );

        $puntos = [];
        $rutas = [];
        $contadorPunto = 0;
        $contadorRuta = 0;

        DB::transaction(
            function () use (
                $empresas,
                $distribucionPuntos,
                $distribucionRutas,
                $usuarioId,
                &$puntos,
                &$rutas,
                &$contadorPunto,
                &$contadorRuta
            ): void {
                foreach (
                    $empresas
                    as $indiceEmpresa => $empresaReferencia
                ) {
                    $empresa = Empresa::query()
                        ->findOrFail(
                            (int) $empresaReferencia['id']
                        );

                    $cantidadPuntos =
                        $distribucionPuntos[$indiceEmpresa];

                    $cantidadRutas =
                        $distribucionRutas[$indiceEmpresa];

                    $puntosEmpresa = [];

                    for (
                        $numeroEmpresa = 1;
                        $numeroEmpresa <= $cantidadPuntos;
                        $numeroEmpresa++
                    ) {
                        $contadorPunto++;

                        $estado =
                            $this->estadoPunto(
                                empresa: $empresa,
                                numeroGlobal: $contadorPunto,
                                numeroEmpresa: $numeroEmpresa
                            );

                        $punto =
                            $this->crearPunto(
                                empresa: $empresa,
                                numeroGlobal: $contadorPunto,
                                numeroEmpresa: $numeroEmpresa,
                                estado: $estado,
                                usuarioId: $usuarioId
                            );

                        $registro = [
                            'id' =>
                                $punto->id,

                            'empresa_id' =>
                                $empresa->id,

                            'empresa_numero' =>
                                $empresaReferencia['numero'],

                            'numero_global' =>
                                $contadorPunto,

                            'numero_empresa' =>
                                $numeroEmpresa,

                            'nombre' =>
                                $punto->nombre,

                            'direccion' =>
                                $punto->direccion,

                            'estado' =>
                                $punto->estado,

                            'empresa_estado' =>
                                $empresa->estado,
                        ];

                        $puntos[] = $registro;
                        $puntosEmpresa[] = $registro;

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'puntos_ruta.'
                                    . 'por_numero.%03d',
                                    $contadorPunto
                                ),
                                $punto->id
                            );

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'puntos_ruta.'
                                    . 'empresa_%02d.%02d',
                                    $empresaReferencia['numero'],
                                    $numeroEmpresa
                                ),
                                $punto->id
                            );

                        CcFlotaSeederContext::
                            registrarEscenario(
                                "punto_ruta.{$punto->id}",
                                $registro
                            );
                    }

                    $pares =
                        $this->paresUnicos(
                            $puntosEmpresa
                        );

                    if (
                        count($pares)
                        < $cantidadRutas
                    ) {
                        throw new RuntimeException(
                            "La empresa {$empresa->id} no posee "
                            . 'suficientes combinaciones de puntos.'
                        );
                    }

                    $pares =
                        $this->rotarPares(
                            $pares,
                            $indiceEmpresa
                        );

                    $paresSeleccionados =
                        array_slice(
                            $pares,
                            0,
                            $cantidadRutas
                        );

                    foreach (
                        $paresSeleccionados
                        as $numeroRutaEmpresa => $par
                    ) {
                        $contadorRuta++;

                        $origen = $par[0];
                        $destino = $par[1];

                        $estado =
                            $this->estadoRuta(
                                empresa: $empresa,
                                origen: $origen,
                                destino: $destino,
                                numeroGlobal: $contadorRuta
                            );

                        $ruta =
                            $this->crearRuta(
                                empresa: $empresa,
                                origen: $origen,
                                destino: $destino,
                                numeroGlobal: $contadorRuta,
                                numeroEmpresa:
                                    $numeroRutaEmpresa + 1,
                                estado: $estado,
                                usuarioId: $usuarioId
                            );

                        $registroRuta = [
                            'id' =>
                                $ruta->id,

                            'empresa_id' =>
                                $empresa->id,

                            'empresa_numero' =>
                                $empresaReferencia['numero'],

                            'numero_global' =>
                                $contadorRuta,

                            'numero_empresa' =>
                                $numeroRutaEmpresa + 1,

                            'punto_origen_id' =>
                                $ruta->punto_origen_id,

                            'punto_destino_id' =>
                                $ruta->punto_destino_id,

                            'punto_origen_nombre' =>
                                $origen['nombre'],

                            'punto_destino_nombre' =>
                                $destino['nombre'],

                            'ruta' =>
                                $ruta->ruta,

                            'kilometros_estimados' =>
                                (float)
                                $ruta->kilometros_estimados,

                            'galones_estimados' =>
                                (float)
                                $ruta->galones_estimados,

                            'rendimiento_estimado' =>
                                $ruta->rendimiento_estimado,

                            'estado' =>
                                $ruta->estado,

                            'empresa_estado' =>
                                $empresa->estado,

                            'puntos_activos' =>
                                $origen['estado'] === 'activo'
                                && $destino['estado'] === 'activo',
                        ];

                        $rutas[] = $registroRuta;

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'rutas.por_numero.%03d',
                                    $contadorRuta
                                ),
                                $ruta->id
                            );

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'rutas.empresa_%02d.%02d',
                                    $empresaReferencia['numero'],
                                    $numeroRutaEmpresa + 1
                                ),
                                $ruta->id
                            );

                        CcFlotaSeederContext::
                            registrarEscenario(
                                "ruta.{$ruta->id}",
                                $registroRuta
                            );
                    }
                }
            },
            3
        );

        if (
            count($puntos)
            !== CcFlotaSeederConfig::TOTAL_PUNTOS_RUTA
        ) {
            throw new RuntimeException(
                'La cantidad creada de puntos de ruta '
                . 'no coincide con la configuración.'
            );
        }

        if (
            count($rutas)
            !== CcFlotaSeederConfig::TOTAL_RUTAS
        ) {
            throw new RuntimeException(
                'La cantidad creada de rutas '
                . 'no coincide con la configuración.'
            );
        }

        $puntosActivos =
            array_values(
                array_filter(
                    $puntos,
                    fn (array $punto): bool =>
                        $punto['estado'] === 'activo'
                )
            );

        $puntosInactivos =
            array_values(
                array_filter(
                    $puntos,
                    fn (array $punto): bool =>
                        $punto['estado'] === 'inactivo'
                )
            );

        $rutasActivas =
            array_values(
                array_filter(
                    $rutas,
                    fn (array $ruta): bool =>
                        $ruta['estado'] === 'activo'
                )
            );

        $rutasInactivas =
            array_values(
                array_filter(
                    $rutas,
                    fn (array $ruta): bool =>
                        $ruta['estado'] === 'inactivo'
                )
            );

        $rutasOperables =
            array_values(
                array_filter(
                    $rutasActivas,
                    fn (array $ruta): bool =>
                        $ruta['empresa_estado'] === 'activa'
                        && $ruta['puntos_activos']
                )
            );

        CcFlotaSeederContext::registrarReferencia(
            'puntos_ruta.todos',
            $puntos
        );

        CcFlotaSeederContext::registrarReferencia(
            'puntos_ruta.activos',
            $puntosActivos
        );

        CcFlotaSeederContext::registrarReferencia(
            'puntos_ruta.inactivos',
            $puntosInactivos
        );

        CcFlotaSeederContext::registrarReferencia(
            'rutas.todas',
            $rutas
        );

        CcFlotaSeederContext::registrarReferencia(
            'rutas.activas',
            $rutasActivas
        );

        CcFlotaSeederContext::registrarReferencia(
            'rutas.inactivas',
            $rutasInactivas
        );

        CcFlotaSeederContext::registrarReferencia(
            'rutas.operables',
            $rutasOperables
        );

        if ($puntosActivos !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'punto_ruta.activo',
                $puntosActivos[0]
            );
        }

        if ($puntosInactivos !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'punto_ruta.inactivo',
                $puntosInactivos[0]
            );
        }

        if ($rutasActivas !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'ruta.activa',
                $rutasActivas[0]
            );
        }

        if ($rutasInactivas !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'ruta.inactiva',
                $rutasInactivas[0]
            );
        }

        $this->command?->line(
            'Puntos de ruta creados: '
            . count($puntos)
        );

        $this->command?->line(
            'Puntos de ruta activos: '
            . count($puntosActivos)
        );

        $this->command?->line(
            'Puntos de ruta inactivos: '
            . count($puntosInactivos)
        );

        $this->command?->line(
            'Rutas creadas: '
            . count($rutas)
        );

        $this->command?->line(
            'Rutas activas: '
            . count($rutasActivas)
        );

        $this->command?->line(
            'Rutas inactivas: '
            . count($rutasInactivas)
        );

        $this->command?->line(
            'Rutas operables: '
            . count($rutasOperables)
        );
    }

    /**
     * Distribuye una cantidad total entre grupos.
     */
    private function distribuirCantidad(
        int $cantidadGrupos,
        int $total
    ): array {
        if (
            $cantidadGrupos <= 0
            || $total < $cantidadGrupos
        ) {
            throw new RuntimeException(
                'La distribución solicitada no es válida.'
            );
        }

        $base = intdiv(
            $total,
            $cantidadGrupos
        );

        $residuo =
            $total % $cantidadGrupos;

        $resultado = [];

        for (
            $indice = 0;
            $indice < $cantidadGrupos;
            $indice++
        ) {
            $resultado[] =
                $base
                + ($indice < $residuo ? 1 : 0);
        }

        return $resultado;
    }

    /**
     * Determina el estado de un punto de ruta.
     */
    private function estadoPunto(
        Empresa $empresa,
        int $numeroGlobal,
        int $numeroEmpresa
    ): string {
        if ($empresa->estado === 'inactiva') {
            return 'inactivo';
        }

        /*
         * Un punto inactivo en empresas seleccionadas permite
         * probar consulta histórica y exclusión de rutas nuevas.
         */
        if (
            $numeroEmpresa === 7
            && $numeroGlobal % 3 === 0
        ) {
            return 'inactivo';
        }

        return 'activo';
    }

    /**
     * Crea un punto de ruta.
     */
    private function crearPunto(
        Empresa $empresa,
        int $numeroGlobal,
        int $numeroEmpresa,
        string $estado,
        int $usuarioId
    ): PuntoRuta {
        $fechaCreacion =
            CcFlotaSeederContext::fechaInicio()
                ->subMonths(
                    2 + ($numeroGlobal % 16)
                )
                ->addDays(
                    ($numeroGlobal * 3) % 25
                )
                ->setTime(
                    7 + ($numeroGlobal % 9),
                    ($numeroGlobal * 7) % 60
                );

        $fechaInactivacion = null;
        $motivoInactivacion = null;
        $inactivadoPor = null;
        $fechaActualizacion = null;
        $actualizadoPor = null;

        if ($estado === 'inactivo') {
            $fechaInactivacion =
                CcFlotaSeederContext::fechaFin()
                    ->subDays(
                        40 + (($numeroGlobal * 11) % 280)
                    )
                    ->setTime(13, 35);

            if (
                $fechaInactivacion->lessThan(
                    $fechaCreacion
                )
            ) {
                $fechaInactivacion =
                    $fechaCreacion->addMonths(4);
            }

            $motivoInactivacion =
                $empresa->estado === 'inactiva'
                    ? 'Punto histórico perteneciente '
                        . 'a una empresa inactiva.'
                    : 'Punto retirado de la operación de rutas.';

            $inactivadoPor = $usuarioId;
            $fechaActualizacion = $fechaInactivacion;
            $actualizadoPor = $usuarioId;
        }

        return PuntoRuta::create([
            'empresa_id' =>
                $empresa->id,

            'nombre' =>
                sprintf(
                    '%s %02d',
                    $this->tipoPunto($numeroEmpresa),
                    $numeroEmpresa
                ),

            'direccion' =>
                $this->direccionPunto(
                    $numeroGlobal
                ),

            'estado' =>
                $estado,

            'fecha_creacion' =>
                $fechaCreacion,

            'creado_por' =>
                $usuarioId,

            'fecha_actualizacion' =>
                $fechaActualizacion,

            'actualizado_por' =>
                $actualizadoPor,

            'fecha_inactivacion' =>
                $fechaInactivacion,

            'inactivado_por' =>
                $inactivadoPor,

            'motivo_inactivacion' =>
                $motivoInactivacion,
        ]);
    }

    /**
     * Genera todas las combinaciones únicas A-B.
     */
    private function paresUnicos(
        array $puntos
    ): array {
        $pares = [];
        $cantidad = count($puntos);

        for (
            $origen = 0;
            $origen < $cantidad - 1;
            $origen++
        ) {
            for (
                $destino = $origen + 1;
                $destino < $cantidad;
                $destino++
            ) {
                $pares[] = [
                    $puntos[$origen],
                    $puntos[$destino],
                ];
            }
        }

        return $pares;
    }

    /**
     * Rota las combinaciones para evitar que todas las empresas
     * reciban exactamente el mismo patrón de rutas.
     */
    private function rotarPares(
        array $pares,
        int $indiceEmpresa
    ): array {
        if ($pares === []) {
            return [];
        }

        $desplazamiento =
            ($indiceEmpresa * 3)
            % count($pares);

        return array_merge(
            array_slice(
                $pares,
                $desplazamiento
            ),
            array_slice(
                $pares,
                0,
                $desplazamiento
            )
        );
    }

    /**
     * Determina el estado de una ruta.
     */
    private function estadoRuta(
        Empresa $empresa,
        array $origen,
        array $destino,
        int $numeroGlobal
    ): string {
        if (
            $empresa->estado === 'inactiva'
            || $origen['estado'] === 'inactivo'
            || $destino['estado'] === 'inactivo'
        ) {
            return 'inactivo';
        }

        return $numeroGlobal % 19 === 0
            ? 'inactivo'
            : 'activo';
    }

    /**
     * Crea una ruta bidireccional.
     */
    private function crearRuta(
        Empresa $empresa,
        array $origen,
        array $destino,
        int $numeroGlobal,
        int $numeroEmpresa,
        string $estado,
        int $usuarioId
    ): Ruta {
        $kilometros =
            round(
                18
                + (($numeroGlobal * 13) % 265)
                + (($numeroEmpresa % 4) * 0.5),
                2
            );

        $rendimientoTeorico =
            round(
                4.8
                + (($numeroGlobal * 7) % 35) / 10,
                2
            );

        $galones =
            round(
                $kilometros
                / $rendimientoTeorico,
                2
            );

        $fechaCreacion =
            CcFlotaSeederContext::fechaInicio()
                ->subMonths(
                    1 + ($numeroGlobal % 14)
                )
                ->addDays(
                    ($numeroGlobal * 5) % 24
                )
                ->setTime(
                    8 + ($numeroGlobal % 8),
                    ($numeroGlobal * 9) % 60
                );

        $fechaInactivacion = null;
        $motivoInactivacion = null;
        $inactivadoPor = null;
        $fechaActualizacion = null;
        $actualizadoPor = null;

        if ($estado === 'inactivo') {
            $fechaInactivacion =
                CcFlotaSeederContext::fechaFin()
                    ->subDays(
                        25 + (($numeroGlobal * 17) % 300)
                    )
                    ->setTime(15, 10);

            if (
                $fechaInactivacion->lessThan(
                    $fechaCreacion
                )
            ) {
                $fechaInactivacion =
                    $fechaCreacion->addMonths(3);
            }

            $motivoInactivacion =
                $empresa->estado === 'inactiva'
                    ? 'Ruta histórica de una empresa inactiva.'
                    : 'Ruta retirada del catálogo operativo.';

            $inactivadoPor = $usuarioId;
            $fechaActualizacion = $fechaInactivacion;
            $actualizadoPor = $usuarioId;
        }

        return Ruta::create([
            'empresa_id' =>
                $empresa->id,

            'punto_origen_id' =>
                $origen['id'],

            'punto_destino_id' =>
                $destino['id'],

            'ruta' =>
                $origen['nombre']
                . ' — '
                . $destino['nombre'],

            'kilometros_estimados' =>
                $kilometros,

            'galones_estimados' =>
                $galones,

            'estado' =>
                $estado,

            'fecha_creacion' =>
                $fechaCreacion,

            'creado_por' =>
                $usuarioId,

            'fecha_actualizacion' =>
                $fechaActualizacion,

            'actualizado_por' =>
                $actualizadoPor,

            'fecha_inactivacion' =>
                $fechaInactivacion,

            'inactivado_por' =>
                $inactivadoPor,

            'motivo_inactivacion' =>
                $motivoInactivacion,
        ]);
    }

    /**
     * Nombre funcional del punto.
     */
    private function tipoPunto(
        int $numeroEmpresa
    ): string {
        $tipos = [
            'Plantel',
            'Bodega',
            'Centro de distribución',
            'Sucursal',
            'Proyecto',
            'Patio logístico',
            'Terminal',
            'Taller',
        ];

        return $tipos[
            ($numeroEmpresa - 1)
            % count($tipos)
        ];
    }

    /**
     * Dirección demostrativa del punto.
     */
    private function direccionPunto(
        int $numeroGlobal
    ): string {
        $ubicaciones = [
            'San Salvador',
            'Santa Tecla',
            'Antiguo Cuscatlán',
            'Soyapango',
            'Apopa',
            'Santa Ana',
            'Sonsonate',
            'Ahuachapán',
            'La Libertad',
            'Zacatecoluca',
            'San Vicente',
            'Cojutepeque',
            'Usulután',
            'San Miguel',
            'La Unión',
            'Chalatenango',
            'Sensuntepeque',
            'Metapán',
        ];

        $ubicacion =
            $ubicaciones[
                ($numeroGlobal - 1)
                % count($ubicaciones)
            ];

        return sprintf(
            'Kilómetro %d, corredor logístico de %s',
            5 + (($numeroGlobal * 7) % 95),
            $ubicacion
        );
    }
}