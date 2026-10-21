<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dateTime('oferta_en')->nullable()->after('precio');
            $table->dateTime('oferta_aceptada_en')->nullable()->after('oferta_en');
            $table->dateTime('oferta_rechazada_en')->nullable()->after('oferta_aceptada_en');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn([
                'oferta_en',
                'oferta_aceptada_en',
                'oferta_rechazada_en',
            ]);
        });
    }
};
