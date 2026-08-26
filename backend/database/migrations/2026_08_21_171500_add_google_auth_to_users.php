<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'google_sub')) {
            Schema::table('users', function (Blueprint $table) {
                $table
                    ->string('google_sub', 255)
                    ->nullable()
                    ->unique('users_google_sub_unique')
                    ->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'google_avatar_url')) {
            Schema::table('users', function (Blueprint $table) {
                $table
                    ->text('google_avatar_url')
                    ->nullable()
                    ->after('google_sub');
            });
        }

        if (! Schema::hasColumn('users', 'google_linked_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table
                    ->timestamp('google_linked_at')
                    ->nullable()
                    ->after('google_avatar_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'google_linked_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('google_linked_at');
            });
        }

        if (Schema::hasColumn('users', 'google_avatar_url')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('google_avatar_url');
            });
        }

        if (Schema::hasColumn('users', 'google_sub')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_google_sub_unique');
                $table->dropColumn('google_sub');
            });
        }
    }
};
