<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        if (! DB::table('users')->where('role', 'super_admin')->exists()) {
            $updated = DB::table('users')
                ->where('email', 'admin@ofd1-pavlovmed.ru')
                ->update(['role' => 'super_admin']);

            if ($updated === 0) {
                $updated = DB::table('users')
                    ->where('role', 'admin')
                    ->orderBy('id')
                    ->limit(1)
                    ->update(['role' => 'super_admin']);
            }

            if ($updated === 0) {
                DB::table('users')
                    ->orderBy('id')
                    ->limit(1)
                    ->update(['role' => 'super_admin']);
            }
        }

        DB::table('users')
            ->whereNotIn('role', ['super_admin', 'admin', 'editor'])
            ->update(['role' => 'editor']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['role' => 'admin']);
    }
};

