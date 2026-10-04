<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 40)->default('admin_general')->change();
        });
    }
    public function down(): void
    {
        // No reducir: truncaría los roles de federación existentes.
    }
};
