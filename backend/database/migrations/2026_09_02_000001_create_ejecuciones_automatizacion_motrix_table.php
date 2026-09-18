<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'ejecuciones_automatizacion_motrix',
            function (Blueprint $table) {
                $table->id();
                $table->string('tipo', 60)
                    ->default('suscripciones');
                $table->string('origen', 30)
                    ->default('scheduler');
                $table->foreignId('ejecutado_por')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->date('fecha_referencia');
                $table->string('estado', 30)
                    ->default('Ejecutando')
                    ->index();
                $table->unsignedInteger('procesadas')
                    ->default(0);
                $table->unsignedInteger('alertas_creadas')
                    ->default(0);
                $table->unsignedInteger('alertas_enviadas')
                    ->default(0);
                $table->unsignedInteger('alertas_sin_usuario')
                    ->default(0);
                $table->unsignedInteger('errores')
                    ->default(0);
                $table->json('detalle')->nullable();
                $table->dateTime('iniciada_en')->index();
                $table->dateTime('finalizada_en')->nullable();
                $table->timestamps();

                $table->index([
                    'tipo',
                    'fecha_referencia',
                    'estado',
                ], 'idx_ejecuciones_motrix_tipo_fecha_estado');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ejecuciones_automatizacion_motrix'
        );
    }
};
