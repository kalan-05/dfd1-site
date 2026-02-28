<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('editor')->after('email');
                $table->index('role');
            }
        });

        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->where('email', 'admin@ofd1-pavlovmed.ru')
                ->update(['role' => 'admin']);

            if (DB::table('users')->where('role', 'admin')->count() === 0) {
                DB::table('users')->orderBy('id')->limit(1)->update(['role' => 'admin']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropIndex(['role']);
                $table->dropColumn('role');
            }
        });
    }
};
