<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
         * Se eliminan registros de prueba antes de simplificar la estructura.
         * Este módulo aún no está en producción funcional, por lo que limpiamos
         * la tabla para evitar datos incompletos con la nueva definición.
         */
        DB::table('gasolineras_externas')->delete();

        Schema::table('gasolineras_externas', function (Blueprint $table) {
            if (Schema::hasColumn('gasolineras_externas', 'nombre')) {
                $table->dropColumn('nombre');
            }

            if (Schema::hasColumn('gasolineras_externas', 'ciudad')) {
                $table->dropColumn('ciudad');
            }

            if (Schema::hasColumn('gasolineras_externas', 'departamento')) {
                $table->dropColumn('departamento');
            }

            if (Schema::hasColumn('gasolineras_externas', 'telefono')) {
                $table->dropColumn('telefono');
            }

            if (Schema::hasColumn('gasolineras_externas', 'correo')) {
                $table->dropColumn('correo');
            }
        });

        /*
         * Compañía y dirección pasan a ser los datos operativos principales.
         */
        Schema::table('gasolineras_externas', function (Blueprint $table) {
            if (Schema::hasColumn('gasolineras_externas', 'compania')) {
                $table->string('compania', 150)->nullable(false)->change();
            }

            if (Schema::hasColumn('gasolineras_externas', 'direccion')) {
                $table->string('direccion', 255)->nullable(false)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gasolineras_externas', function (Blueprint $table) {
            if (! Schema::hasColumn('gasolineras_externas', 'nombre')) {
                $table->string('nombre', 150)->after('empresa_id');
            }

            if (! Schema::hasColumn('gasolineras_externas', 'ciudad')) {
                $table->string('ciudad', 100)->nullable()->after('direccion');
            }

            if (! Schema::hasColumn('gasolineras_externas', 'departamento')) {
                $table->string('departamento', 100)->nullable()->after('ciudad');
            }

            if (! Schema::hasColumn('gasolineras_externas', 'telefono')) {
                $table->string('telefono', 20)->nullable()->after('departamento');
            }

            if (! Schema::hasColumn('gasolineras_externas', 'correo')) {
                $table->string('correo', 150)->nullable()->after('telefono');
            }

            if (Schema::hasColumn('gasolineras_externas', 'compania')) {
                $table->string('compania', 150)->nullable()->change();
            }
        });
    }
};