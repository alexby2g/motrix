<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_suscripcion_motrix', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('descripcion', 500)->nullable();
            $table->decimal('monto', 10, 2);
            $table->unsignedSmallInteger('duracion_meses')->default(1);
            $table->unsignedSmallInteger('dias_gracia')->default(3);
            $table->unsignedSmallInteger('aviso_dias_antes')->default(7);
            $table->boolean('activo')->default(true)->index();
            $table->foreignId('creado_por')->nullable();
            $table->foreignId('actualizado_por')->nullable();
            $table->timestamps();

            $table->foreign('creado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('actualizado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index(['activo', 'monto'], 'planes_motrix_activo_monto_idx');
        });

        Schema::create('suscripciones_motrix', function (Blueprint $table) {
            $table->id();

            // Las tablas existentes mototaxistas y sindicatos usan INT firmado.
            $table->integer('id_mototaxista')->unique();
            $table->integer('id_sindicato')->index();

            $table->foreignId('plan_id');
            $table->date('fecha_inicio')->index();
            $table->date('fecha_vencimiento')->index();
            $table->string('estado', 30)->default('Activa')->index();
            $table->boolean('renovacion_automatica')->default(false);
            $table->dateTime('suspendida_en')->nullable();
            $table->string('motivo_suspension', 500)->nullable();
            $table->foreignId('creado_por')->nullable();
            $table->foreignId('actualizado_por')->nullable();
            $table->timestamps();

            $table->foreign('id_mototaxista')
                ->references('id')
                ->on('mototaxistas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_sindicato')
                ->references('id')
                ->on('sindicatos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('plan_id')
                ->references('id')
                ->on('planes_suscripcion_motrix')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('creado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('actualizado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index(['id_sindicato', 'estado'], 'suscrip_motrix_sind_estado_idx');
            $table->index(['estado', 'fecha_vencimiento'], 'suscrip_motrix_estado_venc_idx');
        });

        Schema::create('pagos_suscripcion_motrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suscripcion_id');

            // Compatibles con los INT firmados existentes.
            $table->integer('id_mototaxista')->index();
            $table->integer('id_sindicato')->index();

            $table->string('periodo', 7)->index();
            $table->decimal('monto_esperado', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->date('fecha_vencimiento')->index();
            $table->dateTime('fecha_pago')->nullable()->index();
            $table->string('estado', 30)->default('Pendiente')->index();
            $table->string('forma_pago', 30)->nullable();
            $table->string('canal_cobro', 30)->default('sindicato')->index();
            $table->string('referencia_pago', 150)->nullable();
            $table->text('comprobante_url')->nullable();
            $table->string('observacion', 500)->nullable();
            $table->foreignId('registrado_por')->nullable();
            $table->foreignId('validado_por')->nullable();
            $table->dateTime('validado_en')->nullable();
            $table->timestamps();

            $table->unique(
                ['suscripcion_id', 'periodo'],
                'pago_suscrip_motrix_periodo_uq'
            );

            $table->foreign('suscripcion_id')
                ->references('id')
                ->on('suscripciones_motrix')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mototaxista')
                ->references('id')
                ->on('mototaxistas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_sindicato')
                ->references('id')
                ->on('sindicatos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('registrado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('validado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index(
                ['id_sindicato', 'periodo', 'estado'],
                'pago_motrix_sind_periodo_estado_idx'
            );

            $table->index(
                ['id_mototaxista', 'estado'],
                'pago_motrix_moto_estado_idx'
            );

            $table->index(
                ['canal_cobro', 'estado'],
                'pago_motrix_canal_estado_idx'
            );
        });

        Schema::create('liquidaciones_motrix', function (Blueprint $table) {
            $table->id();

            // Compatible con sindicatos.id INT firmado.
            $table->integer('id_sindicato')->index();

            $table->string('periodo', 7)->index();
            $table->decimal('monto_declarado', 12, 2)->default(0);
            $table->decimal('monto_transferido', 12, 2)->default(0);
            $table->string('estado', 30)->default('Pendiente')->index();
            $table->string('forma_pago', 30)->nullable();
            $table->string('referencia', 150)->nullable();
            $table->text('comprobante_url')->nullable();
            $table->dateTime('fecha_transferencia')->nullable()->index();
            $table->dateTime('fecha_validacion')->nullable();
            $table->string('observacion', 500)->nullable();
            $table->foreignId('registrado_por')->nullable();
            $table->foreignId('validado_por')->nullable();
            $table->timestamps();

            $table->foreign('id_sindicato')
                ->references('id')
                ->on('sindicatos')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('registrado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('validado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index(
                ['id_sindicato', 'periodo', 'estado'],
                'liquid_motrix_sind_periodo_estado_idx'
            );
        });

        Schema::create('liquidacion_pagos_motrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_id');
            $table->foreignId('pago_suscripcion_id')->unique();
            $table->decimal('monto_incluido', 10, 2);
            $table->timestamps();

            $table->foreign('liquidacion_id')
                ->references('id')
                ->on('liquidaciones_motrix')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('pago_suscripcion_id')
                ->references('id')
                ->on('pagos_suscripcion_motrix')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->index(
                ['liquidacion_id', 'pago_suscripcion_id'],
                'liquid_pago_motrix_rel_idx'
            );
        });

        Schema::create('alertas_suscripcion_motrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suscripcion_id')->nullable();
            $table->foreignId('pago_suscripcion_id')->nullable();

            // Compatibles con INT firmados de las tablas existentes.
            $table->integer('id_mototaxista')->nullable()->index();
            $table->integer('id_sindicato')->nullable()->index();

            $table->foreignId('user_id')->nullable()->index();
            $table->string('tipo', 50)->index();
            $table->string('titulo', 150);
            $table->string('mensaje', 1000);
            $table->string('canal', 30)->default('panel')->index();
            $table->string('estado', 30)->default('Pendiente')->index();
            $table->dateTime('programada_para')->nullable()->index();
            $table->dateTime('enviada_en')->nullable();
            $table->dateTime('leida_en')->nullable();
            $table->timestamps();

            $table->foreign('suscripcion_id')
                ->references('id')
                ->on('suscripciones_motrix')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('pago_suscripcion_id')
                ->references('id')
                ->on('pagos_suscripcion_motrix')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mototaxista')
                ->references('id')
                ->on('mototaxistas')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_sindicato')
                ->references('id')
                ->on('sindicatos')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index(
                ['id_mototaxista', 'estado', 'programada_para'],
                'alerta_motrix_moto_estado_prog_idx'
            );

            $table->index(
                ['id_sindicato', 'estado', 'programada_para'],
                'alerta_motrix_sind_estado_prog_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_suscripcion_motrix');
        Schema::dropIfExists('liquidacion_pagos_motrix');
        Schema::dropIfExists('liquidaciones_motrix');
        Schema::dropIfExists('pagos_suscripcion_motrix');
        Schema::dropIfExists('suscripciones_motrix');
        Schema::dropIfExists('planes_suscripcion_motrix');
    }
};
