<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mototaxistas')) {
            return;
        }

        $faltaRuta = ! Schema::hasColumn('mototaxistas', 'qr_pago_ruta');
        $faltaMetodo = ! Schema::hasColumn('mototaxistas', 'qr_pago_metodo');
        $faltaTitular = ! Schema::hasColumn('mototaxistas', 'qr_pago_titular');
        $faltaActualizado = ! Schema::hasColumn('mototaxistas', 'qr_pago_actualizado_en');

        if ($faltaRuta || $faltaMetodo || $faltaTitular || $faltaActualizado) {
            Schema::table('mototaxistas', function (Blueprint $table) use (
                $faltaRuta,
                $faltaMetodo,
                $faltaTitular,
                $faltaActualizado
            ) {
                if ($faltaRuta) {
                    $table->text('qr_pago_ruta')->nullable()->after('codigo_qr');
                }

                if ($faltaMetodo) {
                    $table->string('qr_pago_metodo', 80)->nullable()->after('qr_pago_ruta');
                }

                if ($faltaTitular) {
                    $table->string('qr_pago_titular', 120)->nullable()->after('qr_pago_metodo');
                }

                if ($faltaActualizado) {
                    $table->dateTime('qr_pago_actualizado_en')->nullable()->after('qr_pago_titular');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('mototaxistas')) {
            return;
        }

        $columnas = array_values(array_filter([
            Schema::hasColumn('mototaxistas', 'qr_pago_actualizado_en') ? 'qr_pago_actualizado_en' : null,
            Schema::hasColumn('mototaxistas', 'qr_pago_titular') ? 'qr_pago_titular' : null,
            Schema::hasColumn('mototaxistas', 'qr_pago_metodo') ? 'qr_pago_metodo' : null,
            Schema::hasColumn('mototaxistas', 'qr_pago_ruta') ? 'qr_pago_ruta' : null,
        ]));

        if ($columnas !== []) {
            Schema::table('mototaxistas', function (Blueprint $table) use ($columnas) {
                $table->dropColumn($columnas);
            });
        }
    }
};
