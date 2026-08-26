<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_sindicales', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_sindicato')->index();
            $table->unsignedInteger('id_mototaxista')->index();
            $table->string('tipo_pago', 30)->index();
            $table->decimal('monto', 10, 2);
            $table->date('fecha')->index();
            $table->string('periodo', 20)->nullable()->index();
            $table->string('estado_pago', 20)->default('Pagado')->index();
            $table->string('forma_pago', 30)->default('Efectivo');
            $table->string('observacion')->nullable();
            $table->foreignId('registrado_por')->nullable()->index();
            $table->timestamps();

            $table->foreign('id_sindicato')
                ->references('id')
                ->on('sindicatos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('id_mototaxista')
                ->references('id')
                ->on('mototaxistas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('registrado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('mensajes_viaje', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('solicitud_id');
            $table->foreignId('remitente_usuario_id')->nullable();
            $table->string('remitente_tipo', 20);
            $table->string('remitente_nombre', 150);
            $table->string('mensaje', 1000);
            $table->dateTime('leido_pasajero_en')->nullable();
            $table->dateTime('leido_conductor_en')->nullable();
            $table->dateTime('creado_en')->useCurrent();

            $table->index(['solicitud_id', 'id'], 'idx_mensajes_viaje_solicitud');
            $table->index('remitente_usuario_id', 'idx_mensajes_viaje_remitente');
            $table->index(
                ['solicitud_id', 'remitente_tipo', 'leido_pasajero_en'],
                'idx_mensajes_viaje_no_leido_pasajero'
            );
            $table->index(
                ['solicitud_id', 'remitente_tipo', 'leido_conductor_en'],
                'idx_mensajes_viaje_no_leido_conductor'
            );

            $table->foreign('solicitud_id')
                ->references('id')
                ->on('solicitudes')
                ->cascadeOnDelete();
            $table->foreign('remitente_usuario_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('incidencias_viaje', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->unsignedInteger('solicitud_id');
            $table->foreignId('reportado_por_usuario_id')->nullable();
            $table->string('reportado_por_rol', 20);
            $table->string('reportado_por_nombre', 150);
            $table->string('tipo', 80)->index();
            $table->string('prioridad', 20)->default('Alta');
            $table->string('descripcion', 500)->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->decimal('precision_metros', 10, 2)->nullable();
            $table->string('estado', 30)->default('Reportado')->index();
            $table->string('nota_administrador', 1000)->nullable();
            $table->foreignId('atendido_por_usuario_id')->nullable();
            $table->string('atendido_por_nombre', 150)->nullable();
            $table->dateTime('fecha_reportada')->index();
            $table->dateTime('recibido_en')->nullable();
            $table->dateTime('atencion_en')->nullable();
            $table->dateTime('resuelto_en')->nullable();
            $table->timestamps();

            $table->index(['solicitud_id', 'estado'], 'incidencias_solicitud_estado_idx');

            $table->foreign('solicitud_id')
                ->references('id')
                ->on('solicitudes')
                ->cascadeOnDelete();
            $table->foreign('reportado_por_usuario_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('atendido_por_usuario_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias_viaje');
        Schema::dropIfExists('mensajes_viaje');
        Schema::dropIfExists('pagos_sindicales');
    }
};
