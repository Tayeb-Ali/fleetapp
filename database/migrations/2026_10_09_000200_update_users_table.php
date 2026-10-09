<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add identity columns (username, role, active, seen_notifications_at)
     * and relax email to nullable. Safe to run on a non-empty users table:
     * new columns are added nullable/with defaults first, existing rows are
     * backfilled, then email is relaxed.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->nullable();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('viewer');
            }
            if (! Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(true);
            }
            if (! Schema::hasColumn('users', 'seen_notifications_at')) {
                $table->timestamp('seen_notifications_at')->nullable();
            }
        });

        // Backfill pre-existing rows (e.g. the default Test User).
        foreach (DB::table('users')->whereNull('username')->orderBy('id')->get() as $row) {
            DB::table('users')->where('id', $row->id)->update([
                'username' => 'user'.$row->id,
                'role' => $row->role ?? 'viewer',
                'active' => $row->active ?? true,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'active', 'seen_notifications_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
