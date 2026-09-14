<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mototaxistas')) {
            $inicializarEstadosSindicales = ! Schema::hasColumn(
                'mototaxistas',
                'estado_sindical'
            );

            Schema::table('mototaxistas', function (Blueprint $table) {
                if (! Schema::hasColumn('mototaxistas', 'documentacion_en_regla')) {
                    $table->boolean('documentacion_en_regla')
                        ->default(false)
                        ->after('estado');
                }

                if (! Schema::hasColumn('mototaxistas', 'aportes_al_dia')) {
                    $table->boolean('aportes_al_dia')
                        ->default(false)
                        ->after('documentacion_en_regla');
                }

                if (! Schema::hasColumn('mototaxistas', 'estado_sindical')) {
                    $table->string('estado_sindical', 30)
                        ->default('No habilitado')
                        ->index()
                        ->after('aportes_al_dia');
                }

                if (! Schema::hasColumn('mototaxistas', 'motivo_estado_sindical')) {
                    $table->string('motivo_estado_sindical', 255)
                        ->nullable()
                        ->after('estado_sindical');
                }

                if (! Schema::hasColumn('mototaxistas', 'estado_sindical_actualizado_en')) {
                    $table->dateTime('estado_sindical_actualizado_en')
                        ->nullable()
                        ->after('motivo_estado_sindical');
                }
            });

            /*
             * Solo inicializar el estado sindical la primera vez que estas
             * columnas son incorporadas. Así, si una migración se interrumpe
             * y debe repetirse, no se sobrescriben decisiones posteriores del
             * sindicato como "Expulsado" o "No habilitado".
             */
            if ($inicializarEstadosSindicales) {
                DB::table('mototaxistas')
                    ->where('estado', 'Activo')
                    ->update([
                        'documentacion_en_regla' => true,
                        'aportes_al_dia' => true,
                        'estado_sindical' => 'Habilitado',
                        'motivo_estado_sindical' => null,
                        'estado_sindical_actualizado_en' => now(),
                    ]);

                DB::table('mototaxistas')
                    ->where(function ($query) {
                        $query->whereNull('estado')
                            ->orWhere('estado', '<>', 'Activo');
                    })
                    ->update([
                        'estado_sindical' => 'No habilitado',
                        'motivo_estado_sindical' => 'Registro administrativo inactivo',
                        'estado_sindical_actualizado_en' => now(),
                    ]);
            }
        }

        if (! Schema::hasTable('password_reset_otps')) {
            Schema::create('password_reset_otps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique();
                $table->string('identifier_hash', 64)->index();
                $table->string('code_hash');
                $table->string('reset_token_hash', 64)->nullable()->index();
                $table->string('canal', 20)->nullable();
                $table->string('destino_enmascarado', 120)->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->dateTime('expires_at')->index();
                $table->dateTime('verified_at')->nullable();
                $table->dateTime('consumed_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('push_devices')) {
            Schema::create('push_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index();
                $table->string('token', 512)->unique();
                $table->string('platform', 30)->nullable();
                $table->string('device_name', 120)->nullable();
                $table->boolean('active')->default(true)->index();
                $table->dateTime('last_seen_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('viaje_compartido_tokens')) {
            Schema::create('viaje_compartido_tokens', function (Blueprint $table) {
                $table->id();

                /*
                 * solicitudes.id en MOTRIX es INT firmado. No usar
                 * unsignedInteger/foreignId aquí porque MySQL exige que ambos
                 * lados de la FK tengan exactamente el mismo signo y tamaño.
                 */
                $table->integer('solicitud_id')->index();
                $table->string('token_hash', 64)->unique();
                $table->foreignId('created_by_user_id')->nullable()->index();
                $table->dateTime('expires_at')->index();
                $table->dateTime('revoked_at')->nullable();
                $table->timestamps();

                $table->foreign('solicitud_id')
                    ->references('id')
                    ->on('solicitudes')
                    ->cascadeOnDelete();

                $table->foreign('created_by_user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        } else {
            /*
             * Recuperación segura para el caso en que MySQL creó la tabla
             * antes de fallar al agregar la FK. No se elimina la tabla ni sus
             * datos; únicamente se corrige el tipo de solicitud_id y se agregan
             * las FK faltantes.
             */
            if (DB::getDriverName() === 'mysql') {
                $database = DB::getDatabaseName();

                $columna = DB::table('information_schema.COLUMNS')
                    ->where('TABLE_SCHEMA', $database)
                    ->where('TABLE_NAME', 'viaje_compartido_tokens')
                    ->where('COLUMN_NAME', 'solicitud_id')
                    ->first(['COLUMN_TYPE']);

                if ($columna && strtolower((string) $columna->COLUMN_TYPE) !== 'int') {
                    DB::statement(
                        'ALTER TABLE viaje_compartido_tokens MODIFY solicitud_id INT NOT NULL'
                    );
                }

                $fkSolicitudExiste = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('TABLE_SCHEMA', $database)
                    ->where('TABLE_NAME', 'viaje_compartido_tokens')
                    ->where('COLUMN_NAME', 'solicitud_id')
                    ->where('REFERENCED_TABLE_NAME', 'solicitudes')
                    ->where('REFERENCED_COLUMN_NAME', 'id')
                    ->exists();

                if (! $fkSolicitudExiste) {
                    Schema::table('viaje_compartido_tokens', function (Blueprint $table) {
                        $table->foreign('solicitud_id')
                            ->references('id')
                            ->on('solicitudes')
                            ->cascadeOnDelete();
                    });
                }

                $fkUsuarioExiste = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('TABLE_SCHEMA', $database)
                    ->where('TABLE_NAME', 'viaje_compartido_tokens')
                    ->where('COLUMN_NAME', 'created_by_user_id')
                    ->where('REFERENCED_TABLE_NAME', 'users')
                    ->where('REFERENCED_COLUMN_NAME', 'id')
                    ->exists();

                if (! $fkUsuarioExiste) {
                    Schema::table('viaje_compartido_tokens', function (Blueprint $table) {
                        $table->foreign('created_by_user_id')
                            ->references('id')
                            ->on('users')
                            ->nullOnDelete();
                    });
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('viaje_compartido_tokens');
        Schema::dropIfExists('push_devices');
        Schema::dropIfExists('password_reset_otps');

        if (Schema::hasTable('mototaxistas')) {
            $columnas = array_values(array_filter([
                Schema::hasColumn('mototaxistas', 'documentacion_en_regla')
                    ? 'documentacion_en_regla' : null,
                Schema::hasColumn('mototaxistas', 'aportes_al_dia')
                    ? 'aportes_al_dia' : null,
                Schema::hasColumn('mototaxistas', 'estado_sindical')
                    ? 'estado_sindical' : null,
                Schema::hasColumn('mototaxistas', 'motivo_estado_sindical')
                    ? 'motivo_estado_sindical' : null,
                Schema::hasColumn('mototaxistas', 'estado_sindical_actualizado_en')
                    ? 'estado_sindical_actualizado_en' : null,
            ]));

            if ($columnas !== []) {
                Schema::table('mototaxistas', function (Blueprint $table) use ($columnas) {
                    $table->dropColumn($columnas);
                });
            }
        }
    }
};
