<?php

namespace Database\Seeders;

use App\Models\Marchamo;
use App\Models\PuntoSeguridadUnidad;
use App\Models\Unidad;
use App\Support\PlantillasPuntosSeguridad;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CcFlotaPuntosMarchamosSeeder extends Seeder
{
    /**
     * Crea los puntos de seguridad y la asignación inicial
     * de marchamos para todas las unidades.
     */
    public function run(): void
    {
        $this->command?->info(
            'Creando puntos de seguridad y marchamos iniciales...'
        );

        $usuarioId =
            CcFlotaSeederContext::superUserId();

        $unidades =
            CcFlotaSeederContext::referencia(
                'unidades.todas'
            );

        if ($unidades === []) {
            throw new RuntimeException(
                'No existen unidades registradas en el contexto.'
            );
        }

        $puntosRegistrados = [];
        $marchamosRegistrados = [];
        $unidadesCoberturaCompleta = [];
        $unidadesCoberturaIncompleta = [];
        $unidadesPendientes = [];

        DB::transaction(
            function () use (
                $unidades,
                $usuarioId,
                &$puntosRegistrados,
                &$marchamosRegistrados,
                &$unidadesCoberturaCompleta,
                &$unidadesCoberturaIncompleta,
                &$unidadesPendientes
            ): void {
                foreach (
                    $unidades
                    as $unidadReferencia
                ) {
                    $unidad = Unidad::query()
                        ->with('empresa')
                        ->findOrFail(
                            (int) $unidadReferencia['id']
                        );

                    $plantilla =
                        (string) $unidadReferencia['plantilla'];

                    $definiciones =
                        PlantillasPuntosSeguridad::porPlantilla(
                            $plantilla
                        );

                    if ($definiciones === []) {
                        throw new RuntimeException(
                            "La plantilla [{$plantilla}] no contiene puntos."
                        );
                    }

                    $modoCobertura =
                        $this->determinarModoCobertura(
                            $unidadReferencia
                        );

                    $cantidadEsperada =
                        count($definiciones);

                    $cantidadAsignada = 0;
                    $puntosUnidad = [];
                    $marchamosUnidad = [];

                    foreach (
                        $definiciones
                        as $definicion
                    ) {
                        $punto =
                            PuntoSeguridadUnidad::create([
                                'unidad_id' =>
                                    $unidad->id,

                                'orden' =>
                                    (int) $definicion['orden'],

                                'codigo_punto' =>
                                    $definicion['codigo_punto']
                                    ?? null,

                                'grupo' =>
                                    $definicion['grupo']
                                    ?? null,

                                'subgrupo' =>
                                    $definicion['subgrupo']
                                    ?? null,

                                'nombre_punto' =>
                                    $definicion['nombre_punto'],

                                'descripcion' =>
                                    null,

                                'posicion_tanque' =>
                                    $definicion['posicion_tanque']
                                    ?? null,

                                'tipo_punto' =>
                                    $definicion['tipo_punto']
                                    ?? null,

                                'requiere_marchamo' =>
                                    (bool) (
                                        $definicion[
                                            'requiere_marchamo'
                                        ]
                                        ?? true
                                    ),

                                'plantilla_origen' =>
                                    $plantilla,

                                'criterio_origen' =>
                                    $definicion[
                                        'criterio_origen'
                                    ]
                                    ?? null,

                                'estado_asignacion' =>
                                    'pendiente',

                                'marchamo_actual_id' =>
                                    null,

                                'estado' =>
                                    'activo',

                                'creado_por' =>
                                    $usuarioId,

                                'actualizado_por' =>
                                    null,

                                'fecha_inactivacion' =>
                                    null,

                                'inactivado_por' =>
                                    null,

                                'motivo_inactivacion' =>
                                    null,
                            ]);

                        $asignar =
                            $this->debeAsignarMarchamo(
                                modoCobertura: $modoCobertura,
                                orden: (int) $definicion['orden'],
                                cantidadEsperada: $cantidadEsperada,
                                requiereMarchamo: (bool) (
                                    $definicion[
                                        'requiere_marchamo'
                                    ]
                                    ?? true
                                )
                            );

                        $marchamo = null;

                        if ($asignar) {
                            $fechaActivacion =
                                $this->fechaAsignacionInicial(
                                    $unidadReferencia
                                );

                            $codigo =
                                CcFlotaSeederContext::codigoNumerico(
                                    'marchamos',
                                    7,
                                    CcFlotaSeederConfig::
                                        MARCHAMO_INICIO
                                );

                            $marchamo =
                                Marchamo::create([
                                    'empresa_id' =>
                                        $unidad->empresa_id,

                                    'unidad_id' =>
                                        $unidad->id,

                                    'punto_seguridad_id' =>
                                        $punto->id,

                                    'codigo_marchamo' =>
                                        $codigo,

                                    'fecha_activacion' =>
                                        $fechaActivacion,

                                    'estado' =>
                                        'activo',

                                    'activo_actual' =>
                                        true,

                                    'fecha_desactivacion' =>
                                        null,

                                    'motivo_desactivacion' =>
                                        null,

                                    'origen_creacion' =>
                                        'asignacion_inicial',

                                    'creado_por' =>
                                        $usuarioId,

                                    'actualizado_por' =>
                                        null,
                                ]);

                            $punto->update([
                                'marchamo_actual_id' =>
                                    $marchamo->id,

                                'estado_asignacion' =>
                                    'asignado',

                                'actualizado_por' =>
                                    $usuarioId,
                            ]);

                            $cantidadAsignada++;

                            $marchamoRegistro = [
                                'id' =>
                                    $marchamo->id,

                                'empresa_id' =>
                                    $unidad->empresa_id,

                                'unidad_id' =>
                                    $unidad->id,

                                'punto_seguridad_id' =>
                                    $punto->id,

                                'codigo_marchamo' =>
                                    $codigo,

                                'estado' =>
                                    'activo',

                                'origen_creacion' =>
                                    'asignacion_inicial',

                                'fecha_activacion' =>
                                    $fechaActivacion
                                        ->toDateTimeString(),
                            ];

                            $marchamosRegistrados[] =
                                $marchamoRegistro;

                            $marchamosUnidad[] =
                                $marchamoRegistro;
                        }

                        $puntoRegistro = [
                            'id' =>
                                $punto->id,

                            'unidad_id' =>
                                $unidad->id,

                            'empresa_id' =>
                                $unidad->empresa_id,

                            'orden' =>
                                $punto->orden,

                            'codigo_punto' =>
                                $punto->codigo_punto,

                            'tipo_punto' =>
                                $punto->tipo_punto,

                            'subgrupo' =>
                                $punto->subgrupo,

                            'posicion_tanque' =>
                                $punto->posicion_tanque,

                            'requiere_marchamo' =>
                                (bool) $punto
                                    ->requiere_marchamo,

                            'estado_asignacion' =>
                                $marchamo
                                    ? 'asignado'
                                    : 'pendiente',

                            'marchamo_actual_id' =>
                                $marchamo?->id,
                        ];

                        $puntosRegistrados[] =
                            $puntoRegistro;

                        $puntosUnidad[] =
                            $puntoRegistro;
                    }

                    $registroCobertura = [
                        'unidad_id' =>
                            $unidad->id,

                        'empresa_id' =>
                            $unidad->empresa_id,

                        'placa' =>
                            $unidad->placa,

                        'estado_unidad' =>
                            $unidad->estado,

                        'condicion_licencia' =>
                            $unidadReferencia[
                                'condicion_licencia'
                            ],

                        'plantilla' =>
                            $plantilla,

                        'modo_cobertura' =>
                            $modoCobertura,

                        'puntos_esperados' =>
                            $cantidadEsperada,

                        'puntos_asignados' =>
                            $cantidadAsignada,

                        'cobertura_completa' =>
                            $cantidadAsignada
                            === $cantidadEsperada,

                        'puntos' =>
                            $puntosUnidad,

                        'marchamos' =>
                            $marchamosUnidad,
                    ];

                    CcFlotaSeederContext::
                        registrarReferencia(
                            "seguridad.unidad.{$unidad->id}",
                            $registroCobertura
                        );

                    if (
                        $registroCobertura[
                            'cobertura_completa'
                        ]
                    ) {
                        $unidadesCoberturaCompleta[] =
                            $registroCobertura;
                    } elseif ($cantidadAsignada === 0) {
                        $unidadesPendientes[] =
                            $registroCobertura;
                    } else {
                        $unidadesCoberturaIncompleta[] =
                            $registroCobertura;
                    }
                }
            },
            3
        );

        CcFlotaSeederContext::registrarReferencia(
            'puntos_seguridad.todos',
            $puntosRegistrados
        );

        CcFlotaSeederContext::registrarReferencia(
            'marchamos.iniciales',
            $marchamosRegistrados
        );

        CcFlotaSeederContext::registrarReferencia(
            'unidades.cobertura_completa',
            $unidadesCoberturaCompleta
        );

        CcFlotaSeederContext::registrarReferencia(
            'unidades.cobertura_incompleta',
            $unidadesCoberturaIncompleta
        );

        CcFlotaSeederContext::registrarReferencia(
            'unidades.asignacion_pendiente',
            $unidadesPendientes
        );

        $unidadesOperables =
            array_values(
                array_filter(
                    $unidadesCoberturaCompleta,
                    fn (array $unidad): bool =>
                        $unidad['estado_unidad']
                            === 'activa'
                        && $unidad['condicion_licencia']
                            === 'vigente'
                )
            );

        CcFlotaSeederContext::registrarReferencia(
            'unidades.operables',
            $unidadesOperables
        );

        if ($unidadesOperables === []) {
            throw new RuntimeException(
                'No se generaron unidades completamente operables.'
            );
        }

        CcFlotaSeederContext::registrarEscenario(
            'seguridad.cobertura_completa',
            $unidadesCoberturaCompleta[0]
        );

        if ($unidadesCoberturaIncompleta !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'seguridad.cobertura_incompleta',
                $unidadesCoberturaIncompleta[0]
            );
        }

        if ($unidadesPendientes !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'seguridad.asignacion_pendiente',
                $unidadesPendientes[0]
            );
        }

        $this->command?->line(
            'Puntos de seguridad creados: '
            . count($puntosRegistrados)
        );

        $this->command?->line(
            'Marchamos iniciales creados: '
            . count($marchamosRegistrados)
        );

        $this->command?->line(
            'Unidades con cobertura completa: '
            . count($unidadesCoberturaCompleta)
        );

        $this->command?->line(
            'Unidades con cobertura incompleta: '
            . count($unidadesCoberturaIncompleta)
        );

        $this->command?->line(
            'Unidades con asignación pendiente: '
            . count($unidadesPendientes)
        );

        $this->command?->line(
            'Unidades operables: '
            . count($unidadesOperables)
        );
    }

    /**
     * Determina la condición de cobertura de cada unidad.
     */
    private function determinarModoCobertura(
        array $unidad
    ): string {
        if ($unidad['estado'] === 'registrada') {
            return 'pendiente';
        }

        if (
            $unidad['estado'] === 'activa'
            && $unidad['condicion_licencia'] === 'vigente'
            && $unidad['numero_global'] % 17 === 0
        ) {
            return 'incompleta';
        }

        if (
            $unidad['condicion_licencia'] === 'sin_licencia'
        ) {
            return 'pendiente';
        }

        return 'completa';
    }

    /**
     * Determina si un punto específico recibirá marchamo inicial.
     */
    private function debeAsignarMarchamo(
        string $modoCobertura,
        int $orden,
        int $cantidadEsperada,
        bool $requiereMarchamo
    ): bool {
        if (! $requiereMarchamo) {
            return false;
        }

        return match ($modoCobertura) {
            'completa' =>
                true,

            'pendiente' =>
                false,

            'incompleta' =>
                $orden < $cantidadEsperada,

            default =>
                throw new RuntimeException(
                    "Modo de cobertura inválido: {$modoCobertura}"
                ),
        };
    }

    /**
     * Genera una fecha histórica coherente para la asignación inicial.
     */
    private function fechaAsignacionInicial(
        array $unidad
    ): CarbonImmutable {
        return CcFlotaSeederContext::fechaInicio()
            ->subDays(
                30
                + (
                    (int) $unidad['numero_global']
                    % 120
                )
            )
            ->setTime(
                8
                + (
                    (int) $unidad['numero_global']
                    % 8
                ),
                (
                    (int) $unidad['numero_global']
                    * 7
                ) % 60
            );
    }
}