<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nickname', 50)->nullable()->unique()->after('name');
            $table->string('role', 20)->default('admin_general')->index()->after('password');

            $table->unsignedInteger('mototaxista_id')->nullable()->index()->after('role');
            $table->unsignedInteger('pasajero_id')->nullable()->index()->after('mototaxista_id');
            $table->unsignedInteger('persona_id')->nullable()->index()->after('pasajero_id');
            $table->unsignedInteger('federacion_id')->nullable()->index()->after('persona_id');
            $table->unsignedInteger('sindicato_id')->nullable()->index()->after('federacion_id');

            $table->foreign('mototaxista_id')
                ->references('id')
                ->on('mototaxistas')
                ->nullOnDelete();
            $table->foreign('pasajero_id')
                ->references('id')
                ->on('pasajeros')
                ->nullOnDelete();
            $table->foreign('persona_id')
                ->references('id')
                ->on('personas')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('federacion_id')
                ->references('id')
                ->on('federaciones')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('sindicato_id')
                ->references('id')
                ->on('sindicatos')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['mototaxista_id']);
            $table->dropForeign(['pasajero_id']);
            $table->dropForeign(['persona_id']);
            $table->dropForeign(['federacion_id']);
            $table->dropForeign(['sindicato_id']);

            $table->dropColumn([
                'nickname',
                'role',
                'mototaxista_id',
                'pasajero_id',
                'persona_id',
                'federacion_id',
                'sindicato_id',
            ]);
        });
    }
};
