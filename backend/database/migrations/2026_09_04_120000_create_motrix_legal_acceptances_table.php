<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('motrix_legal_acceptances')) {
            return;
        }

        Schema::create('motrix_legal_acceptances', function (Blueprint $table) {
            $table->id();

            /*
             * No se declara foreign key para evitar conflictos con bases
             * históricas cuya secuencia de migraciones local está
             * desincronizada. El user_id sigue quedando indexado y auditable.
             */
            $table->unsignedBigInteger('user_id')->index();
            $table->string('role_at_acceptance', 40);

            $table->string('terms_version', 30);
            $table->string('privacy_version', 30);

            $table->timestamp('accepted_at');
            $table->string('channel', 30)->default('web');

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'user_id',
                    'terms_version',
                    'privacy_version',
                ],
                'motrix_legal_accept_user_versions_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motrix_legal_acceptances');
    }
};
