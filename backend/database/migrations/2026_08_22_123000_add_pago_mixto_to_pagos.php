<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pagos', 'monto_efectivo')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->decimal(
                    'monto_efectivo',
                    10,
                    2
                )->nullable()->after('monto');
            });
        }

        if (! Schema::hasColumn('pagos', 'monto_qr')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->decimal(
                    'monto_qr',
                    10,
                    2
                )->nullable()->after('monto_efectivo');
            });
        }

        DB::table('pagos')
            ->where('metodo', 'Efectivo')
            ->whereNull('monto_efectivo')
            ->update([
                'monto_efectivo' => DB::raw('monto'),
                'monto_qr' => 0,
            ]);

        DB::table('pagos')
            ->where('metodo', '<>', 'Efectivo')
            ->whereNull('monto_qr')
            ->update([
                'monto_efectivo' => 0,
                'monto_qr' => DB::raw('monto'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('pagos', 'monto_qr')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->dropColumn('monto_qr');
            });
        }

        if (Schema::hasColumn('pagos', 'monto_efectivo')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->dropColumn('monto_efectivo');
            });
        }
    }
};
