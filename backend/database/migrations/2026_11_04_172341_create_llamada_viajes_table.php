<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llamada_viajes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('solicitud_id');
            $table->unsignedBigInteger('iniciador_user_id');
            $table->unsignedBigInteger('receptor_user_id');

            $table->string('estado', 20)->default('pendiente');

            $table->timestamp('expira_en')->nullable();
            $table->timestamp('contestada_en')->nullable();
            $table->timestamp('finalizada_en')->nullable();

            $table->timestamps();

            $table->index(['solicitud_id', 'estado']);
            $table->index(['receptor_user_id', 'estado']);
            $table->index(['iniciador_user_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llamada_viajes');
    }
};