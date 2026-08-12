<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $nickname = 'Alexby2g';
        $email = 'alexby2g@motrix.local';

        $existente = DB::table('users')
            ->whereRaw('LOWER(nickname) = ?', [strtolower($nickname)])
            ->first();

        $datos = [
            'name' => 'Alexander Guzmán',
            'nickname' => $nickname,
            'email' => $email,
            'password' => '$2y$12$0N3Ihx.zpWZHerROpANAEei4eKA.lioytc9tk6YmAu0ubSyJS6XCe',
            'role' => 'admin_general',
            'updated_at' => now(),
        ];

        if ($existente) {
            DB::table('users')
                ->where('id', $existente->id)
                ->update($datos);

            return;
        }

        DB::table('users')->insert([
            ...$datos,
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereRaw('LOWER(nickname) = ?', ['alexby2g'])
            ->where('email', 'alexby2g@motrix.local')
            ->delete();
    }
};
