<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Distinguishes an account that actually knows its own password
     * (self-registered, or reset via an emailed link) from one created
     * with an unknowable random password (guest checkout, staff-created
     * walk-in) - the Profile page uses this to hide the Change Password
     * form for the latter, since "enter your current password" is
     * meaningless when there's no way they could know it.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_set_at')->nullable()->after('password');
        });

        // Every existing account was either self-registered or seeded as a
        // real staff/admin login - none of them are guest/walk-in accounts
        // with an unknowable password, so backfill them all as "known".
        // Only accounts created after this point can end up null.
        DB::table('users')->update(['password_set_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });
    }
};
