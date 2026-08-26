<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $administradores = [
            [
                'nombre' => 'Alexander',
                'apellidos' => 'Guzmán',
                'nombre_completo' => 'Alexander Guzmán',
                'nickname' => 'Alexby2g',
                'email' => 'alexby2g@motrix.local',
                'password_hash' => null,
            ],
            [
                'nombre' => 'Madisson',
                'apellidos' => null,
                'nombre_completo' => 'Madisson',
                'nickname' => 'madis123',
                'email' => 'madis123@motrix.local',
                'password_hash' => '$2y$12$huVq2VNp6mGbebDgOXwPfOZphHpLmWT/UHK1MiMHuBwKoDDUdTZ1u',
            ],
            [
                'nombre' => 'Rodrigo',
                'apellidos' => null,
                'nombre_completo' => 'Rodrigo',
                'nickname' => 'rodrigo123',
                'email' => 'rodrigo123@motrix.local',
                'password_hash' => '$2y$12$zRuICBfOk3YiFhbAc9rg/.vsysPPmTm9e2bmsWBXhaFlO2YnHyrPW',
            ],
        ];

        foreach ($administradores as $admin) {
            $personaId = DB::table('personas')
                ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($admin['nombre'])])
                ->when(
                    $admin['apellidos'],
                    fn ($query) => $query->whereRaw(
                        'LOWER(COALESCE(apellidos, \'\')) = ?',
                        [mb_strtolower($admin['apellidos'])]
                    ),
                    fn ($query) => $query->whereNull('apellidos')
                )
                ->value('id');

            if (! $personaId) {
                $personaId = DB::table('personas')->insertGetId([
                    'nombre' => $admin['nombre'],
                    'apellidos' => $admin['apellidos'],
                    'telefono' => null,
                    'ci' => null,
                    'direccion' => null,
                    'sindicato_registro_id' => null,
                ]);
            }

            $usuario = DB::table('users')
                ->whereRaw('LOWER(nickname) = ?', [mb_strtolower($admin['nickname'])])
                ->first();

            $datos = [
                'name' => $admin['nombre_completo'],
                'nickname' => $admin['nickname'],
                'email' => $admin['email'],
                'role' => 'admin_general',
                'persona_id' => $personaId,
                'mototaxista_id' => null,
                'pasajero_id' => null,
                'federacion_id' => null,
                'sindicato_id' => null,
                'updated_at' => now(),
            ];

            if ($admin['password_hash']) {
                $datos['password'] = $admin['password_hash'];
            }

            if ($usuario) {
                DB::table('users')
                    ->where('id', $usuario->id)
                    ->update($datos);

                continue;
            }

            DB::table('users')->insert([
                ...$datos,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('nickname', ['madis123', 'rodrigo123'])
            ->delete();

        foreach (['Madisson', 'Rodrigo'] as $nombre) {
            $personaId = DB::table('personas')
                ->where('nombre', $nombre)
                ->whereNull('apellidos')
                ->value('id');

            if (! $personaId) {
                continue;
            }

            $enUso = DB::table('users')->where('persona_id', $personaId)->exists()
                || DB::table('mototaxistas')->where('id_persona', $personaId)->exists()
                || DB::table('pasajeros')->where('id_persona', $personaId)->exists();

            if (! $enUso) {
                DB::table('personas')->where('id', $personaId)->delete();
            }
        }
    }
};
