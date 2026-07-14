<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // nullable -> OAuth users don't need a password
            $table->string('password')->nullable()->change();
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('avatar_key')->nullable()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
            $table->dropColumn(['username', 'avatar_key']);
        });
    }
};
