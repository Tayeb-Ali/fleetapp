<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->nullable()->after('id');
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 32)->default('viewer')->after('email');
            }
            if (! Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(true)->after('role');
            }
        });
    }

    public function down(): void
    {
        // Keep columns on rollback to avoid data loss; drop only if they exist and were added here.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'active')) {
                $table->dropColumn('active');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};
