<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personas') || ! Schema::hasColumn('personas', 'ci')) {
            return;
        }

        // En las pruebas SQLite la migración base ya crea CI como nullable.
        // La alteración se necesita para instalaciones MySQL/MariaDB antiguas.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('personas', function (Blueprint $table) {
            $table->string('ci', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        // No se fuerza NOT NULL al revertir: podrían existir pasajeros/personas
        // registrados legítimamente sin CI y no se deben perder ni invalidar datos.
    }
};
