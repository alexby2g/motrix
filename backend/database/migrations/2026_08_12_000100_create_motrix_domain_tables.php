<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federaciones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 150)->unique();
            $table->string('logo')->nullable();
        });

        Schema::create('sindicatos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->string('federacion', 100)->nullable();
            $table->unsignedInteger('id_federacion')->nullable()->index();
            $table->string('logo')->nullable();
            $table->string('direccion')->nullable();
            $table->date('fecha_creacion')->nullable();

            $table->foreign('id_federacion')
                ->references('id')
                ->on('federaciones')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('personas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100)->nullable();
            $table->string('apellidos', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('ci', 20)->nullable()->unique();
            $table->string('direccion', 150)->nullable();
            $table->unsignedInteger('sindicato_registro_id')->nullable()->index();

            $table->foreign('sindicato_registro_id')
                ->references('id')
                ->on('sindicatos')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('pasajeros', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 100)->nullable();
            $table->string('password')->nullable();
            $table->unsignedInteger('id_persona')->nullable()->index();

            $table->foreign('id_persona')
                ->references('id')
                ->on('personas');
        });

        Schema::create('mototaxistas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nro_chaleco', 20)->nullable();
            $table->string('codigo_qr', 36)->nullable()->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('estado', 50)->nullable();
            $table->boolean('disponible')->default(false)->index();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->dateTime('ultima_conexion')->nullable();
            $table->unsignedInteger('id_persona')->nullable()->index();
            $table->unsignedInteger('id_sindicato')->nullable()->index();

            $table->unique(
                ['id_sindicato', 'nro_chaleco'],
                'mototaxistas_sindicato_chaleco_unique'
            );
            $table->index(['latitud', 'longitud'], 'idx_mototaxistas_ubicacion');

            $table->foreign('id_persona')
                ->references('id')
                ->on('personas');
            $table->foreign('id_sindicato')
                ->references('id')
                ->on('sindicatos');
        });

        Schema::create('motocicletas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('placa', 20)->nullable()->unique();
            $table->string('chasis', 50)->nullable()->unique();
            $table->string('modelo', 50)->nullable();
            $table->string('color', 50)->nullable();
            $table->boolean('tiene_soat')->default(false);
            $table->unsignedInteger('id_mototaxista')->nullable()->index();

            $table->foreign('id_mototaxista')
                ->references('id')
                ->on('mototaxistas');
        });

        Schema::create('solicitudes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('origen', 150)->nullable();
            $table->decimal('latitud_origen', 10, 7)->nullable();
            $table->decimal('longitud_origen', 10, 7)->nullable();
            $table->string('destino', 150)->nullable();
            $table->decimal('latitud_destino', 10, 7)->nullable();
            $table->decimal('longitud_destino', 10, 7)->nullable();
            $table->date('fecha')->nullable();
            $table->dateTime('creado_en')->useCurrent();
            $table->string('estado', 50)->nullable();
            $table->unsignedInteger('id_pasajero')->nullable()->index();
            $table->decimal('precio', 8, 2)->default(0);
            $table->decimal('distancia_km', 8, 2)->nullable();
            $table->unsignedInteger('mototaxista_id')->nullable()->index();
            $table->string('metodo_pago')->nullable();
            $table->dateTime('expira_en')->nullable();
            $table->string('motivo_cancelacion')->nullable();
            $table->unsignedTinyInteger('calificacion')->nullable();
            $table->string('comentario_calificacion', 500)->nullable();
            $table->dateTime('calificado_en')->nullable();

            $table->foreign('id_pasajero')
                ->references('id')
                ->on('pasajeros');
            $table->foreign('mototaxista_id')
                ->references('id')
                ->on('mototaxistas')
                ->nullOnDelete();
        });

        Schema::create('servicios', function (Blueprint $table) {
            $table->increments('id');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('estado', 50)->nullable();
            $table->unsignedInteger('id_solicitud')->nullable()->index();
            $table->unsignedInteger('id_mototaxista')->nullable()->index();

            $table->foreign('id_solicitud')
                ->references('id')
                ->on('solicitudes');
            $table->foreign('id_mototaxista')
                ->references('id')
                ->on('mototaxistas');
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('monto', 10, 2)->nullable();
            $table->string('metodo', 50)->nullable();
            $table->string('estado', 50)->nullable();
            $table->unsignedInteger('id_servicio')->nullable()->index();

            $table->foreign('id_servicio')
                ->references('id')
                ->on('servicios');
        });

        Schema::create('imagenes_personas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ruta');
            $table->string('tipo', 50)->nullable();
            $table->unsignedInteger('id_persona')->index();

            $table->foreign('id_persona')
                ->references('id')
                ->on('personas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('imagenes_motocicletas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ruta');
            $table->string('tipo', 20);
            $table->unsignedInteger('id_motocicleta')->index();

            $table->foreign('id_motocicleta')
                ->references('id')
                ->on('motocicletas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::create('imagenes_sindicatos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ruta');
            $table->string('tipo', 20);
            $table->unsignedInteger('id_sindicato')->index();

            $table->foreign('id_sindicato')
                ->references('id')
                ->on('sindicatos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagenes_sindicatos');
        Schema::dropIfExists('imagenes_motocicletas');
        Schema::dropIfExists('imagenes_personas');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('servicios');
        Schema::dropIfExists('solicitudes');
        Schema::dropIfExists('motocicletas');
        Schema::dropIfExists('mototaxistas');
        Schema::dropIfExists('pasajeros');
        Schema::dropIfExists('personas');
        Schema::dropIfExists('sindicatos');
        Schema::dropIfExists('federaciones');
    }
};
