<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ResetCcFlotaDataSeeder extends Seeder
{
    /**
     * Tablas funcionales y operativas que serán reconstruidas desde cero.
     *
     * No se incluyen users, roles, permisos ni rol_permisos.
     */
    private const TABLAS_A_LIMPIAR = [
        'abastecimiento_rutas',
        'abastecimiento_tanques',
        'movimientos_inventario_combustible',
        'reemplazo_marchamos_detalle',
        'reemplazo_marchamos_eventos',
        'abastecimientos',
        'recargas_combustible',
        'marchamos',
        'puntos_seguridad_unidad',
        'rutas',
        'puntos_ruta',
        'motoristas',
        'tanques',
        'gasolineras_externas',
        'gasolineras',
        'licencias',
        'unidades',
        'empresas',
    ];

    /**
     * Ejecuta una limpieza controlada de los datos funcionales de CC-Flota.
     *
     * Reglas de seguridad:
     * - debe existir exactamente un usuario;
     * - debe estar activo;
     * - debe ser un usuario global, sin empresa asociada;
     * - debe poseer el rol DIESEL_SUPER_ADMIN;
     * - las tablas de autenticación, roles y permisos no se alteran.
     */
    public function run(): void
    {
        $this->validarSuperusuario();
        $this->validarMotorBaseDeDatos();
        $this->validarTablasEsperadas();

        $this->command?->warn(
            'Iniciando limpieza controlada de datos funcionales de CC-Flota...'
        );

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (self::TABLAS_A_LIMPIAR as $tabla) {
                DB::table($tabla)->truncate();

                $this->command?->line(
                    "  Tabla limpiada: {$tabla}"
                );
            }
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'No fue posible completar la limpieza controlada de CC-Flota. '
                . 'La tabla que se estaba procesando era: '
                . ($tabla ?? 'desconocida')
                . '. Error original: '
                . $exception->getMessage(),
                previous: $exception
            );
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->validarResultado();

        $this->command?->info(
            'Limpieza funcional completada. El superusuario, los roles y los permisos permanecen intactos.'
        );
    }

    /**
     * Verifica que la cuenta administrativa que debe preservarse sea la única
     * cuenta existente y conserve su condición global.
     */
    private function validarSuperusuario(): void
    {
        $cantidadUsuarios = User::query()->count();

        if ($cantidadUsuarios !== 1) {
            throw new RuntimeException(
                'La reconstrucción fue cancelada: se esperaba exactamente un '
                . "usuario y se encontraron {$cantidadUsuarios}."
            );
        }

        $usuario = User::query()
            ->with('role')
            ->first();

        if (! $usuario) {
            throw new RuntimeException(
                'La reconstrucción fue cancelada: no fue posible recuperar el superusuario.'
            );
        }

        if ($usuario->estado !== 'activo') {
            throw new RuntimeException(
                'La reconstrucción fue cancelada: el único usuario no está activo.'
            );
        }

        if (! is_null($usuario->empresa_id)) {
            throw new RuntimeException(
                'La reconstrucción fue cancelada: el superusuario está asociado a una empresa.'
            );
        }

        if ($usuario->role?->codigo !== 'DIESEL_SUPER_ADMIN') {
            throw new RuntimeException(
                'La reconstrucción fue cancelada: el único usuario no posee el rol DIESEL_SUPER_ADMIN.'
            );
        }

        $this->command?->info(
            "Superusuario preservado: {$usuario->email} (ID {$usuario->id})."
        );
    }

    /**
     * El procedimiento usa instrucciones específicas de MySQL.
     */
    private function validarMotorBaseDeDatos(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'ResetCcFlotaDataSeeder solo puede ejecutarse con una conexión MySQL.'
            );
        }
    }

    /**
     * Evita comenzar la limpieza si el esquema no coincide con el esperado.
     */
    private function validarTablasEsperadas(): void
    {
        $baseDatos = DB::getDatabaseName();

        $tablasExistentes = collect(
            DB::select(
                'SELECT TABLE_NAME AS nombre '
                . 'FROM information_schema.TABLES '
                . 'WHERE TABLE_SCHEMA = ?',
                [$baseDatos]
            )
        )
            ->pluck('nombre')
            ->all();

        $faltantes = array_values(
            array_diff(
                self::TABLAS_A_LIMPIAR,
                $tablasExistentes
            )
        );

        if ($faltantes !== []) {
            throw new RuntimeException(
                'La reconstrucción fue cancelada porque faltan estas tablas: '
                . implode(', ', $faltantes)
                . '.'
            );
        }
    }

    /**
     * Confirma que todas las tablas previstas quedaron vacías y que el usuario
     * preservado continúa disponible.
     */
    private function validarResultado(): void
    {
        $tablasConDatos = [];

        foreach (self::TABLAS_A_LIMPIAR as $tabla) {
            if (DB::table($tabla)->exists()) {
                $tablasConDatos[] = $tabla;
            }
        }

        if ($tablasConDatos !== []) {
            throw new RuntimeException(
                'La limpieza finalizó con datos residuales en estas tablas: '
                . implode(', ', $tablasConDatos)
                . '.'
            );
        }

        $this->validarSuperusuario();
    }
}