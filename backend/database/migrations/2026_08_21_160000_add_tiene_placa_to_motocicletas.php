<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('motocicletas', 'tiene_placa')) {
            Schema::table('motocicletas', function (Blueprint $table) {
                $table->boolean('tiene_placa')
                    ->default(true);
            });
        }

        DB::table('motocicletas')
            ->whereNull('placa')
            ->orWhere('placa', '')
            ->update([
                'tiene_placa' => false,
                'placa' => null,
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('motocicletas', 'tiene_placa')) {
            Schema::table('motocicletas', function (Blueprint $table) {
                $table->dropColumn('tiene_placa');
            });
        }
    }
};
