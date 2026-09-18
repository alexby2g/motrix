<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'liquidaciones_motrix',
            function (Blueprint $table) {
                $table->unique(
                    [
                        'id_sindicato',
                        'periodo',
                    ],
                    'liquid_motrix_sind_periodo_uq'
                );
            }
        );

        Schema::create(
            'transferencias_liquidacion_motrix',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'liquidacion_id'
                );

                $table->decimal(
                    'monto',
                    12,
                    2
                );

                $table->string(
                    'forma_pago',
                    30
                );

                $table->string(
                    'referencia',
                    150
                )->nullable();

                $table->text(
                    'comprobante_url'
                )->nullable();

                $table->dateTime(
                    'fecha_transferencia'
                )->index();

                $table->string(
                    'estado',
                    30
                )
                    ->default('Pendiente')
                    ->index();

                $table->string(
                    'observacion',
                    500
                )->nullable();

                $table->foreignId(
                    'registrado_por'
                )->nullable();

                $table->foreignId(
                    'validado_por'
                )->nullable();

                $table->dateTime(
                    'validado_en'
                )->nullable();

                $table->timestamps();

                $table->foreign(
                    'liquidacion_id'
                )
                    ->references('id')
                    ->on(
                        'liquidaciones_motrix'
                    )
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->foreign(
                    'registrado_por'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();

                $table->foreign(
                    'validado_por'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();

                $table->index(
                    [
                        'liquidacion_id',
                        'estado',
                        'fecha_transferencia',
                    ],
                    'transf_liquid_motrix_estado_fecha_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'transferencias_liquidacion_motrix'
        );

        Schema::table(
            'liquidaciones_motrix',
            function (Blueprint $table) {
                $table->dropUnique(
                    'liquid_motrix_sind_periodo_uq'
                );
            }
        );
    }
};
