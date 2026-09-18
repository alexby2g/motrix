<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mototaxistas', function (Blueprint $table) {
            $table->index(
                [
                    'estado',
                    'disponible',
                    'ultima_conexion',
                ],
                'mototaxistas_estado_disp_conexion_idx'
            );

            $table->index(
                [
                    'disponible',
                    'latitud',
                    'longitud',
                ],
                'mototaxistas_disp_ubicacion_idx'
            );
        });

        Schema::table('solicitudes', function (Blueprint $table) {
            $table->index(
                [
                    'mototaxista_id',
                    'estado',
                    'expira_en',
                ],
                'solicitudes_moto_estado_expira_idx'
            );

            $table->index(
                [
                    'id_pasajero',
                    'estado',
                    'id',
                ],
                'solicitudes_pasajero_estado_id_idx'
            );

            $table->index(
                [
                    'estado',
                    'expira_en',
                    'id',
                ],
                'solicitudes_estado_expira_id_idx'
            );


            $table->index(
                [
                    'latitud_origen',
                    'longitud_origen',
                ],
                'solicitudes_ubicacion_origen_idx'
            );
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->index(
                [
                    'id_mototaxista',
                    'estado',
                    'id',
                ],
                'servicios_moto_estado_id_idx'
            );
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->index(
                [
                    'estado',
                    'metodo',
                    'id',
                ],
                'pagos_estado_metodo_id_idx'
            );
        });

        Schema::table('pagos_sindicales', function (Blueprint $table) {
            $table->index(
                [
                    'id_sindicato',
                    'fecha',
                    'id',
                ],
                'pagos_sindicato_fecha_id_idx'
            );

            $table->index(
                [
                    'id_mototaxista',
                    'fecha',
                    'id',
                ],
                'pagos_moto_fecha_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pagos_sindicales', function (Blueprint $table) {
            $table->dropIndex(
                'pagos_sindicato_fecha_id_idx'
            );
            $table->dropIndex(
                'pagos_moto_fecha_id_idx'
            );
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex(
                'pagos_estado_metodo_id_idx'
            );
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropIndex(
                'servicios_moto_estado_id_idx'
            );
        });

        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropIndex(
                'solicitudes_moto_estado_expira_idx'
            );
            $table->dropIndex(
                'solicitudes_pasajero_estado_id_idx'
            );
            $table->dropIndex(
                'solicitudes_estado_expira_id_idx'
            );
            $table->dropIndex(
                'solicitudes_ubicacion_origen_idx'
            );
        });

        Schema::table('mototaxistas', function (Blueprint $table) {
            $table->dropIndex(
                'mototaxistas_estado_disp_conexion_idx'
            );
            $table->dropIndex(
                'mototaxistas_disp_ubicacion_idx'
            );
        });
    }
};
