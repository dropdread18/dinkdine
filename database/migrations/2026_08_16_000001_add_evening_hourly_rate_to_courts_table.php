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
        Schema::table('courts', function (Blueprint $table) {
            $table->decimal('evening_hourly_rate', 8, 2)->after('hourly_rate');
        });

        // Existing rows have no evening rate yet - default to the day rate
        // rather than leaving it null, so nothing silently prices at ₱0
        // until an admin edits every court by hand.
        DB::table('courts')->update(['evening_hourly_rate' => DB::raw('hourly_rate')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courts', function (Blueprint $table) {
            $table->dropColumn('evening_hourly_rate');
        });
    }
};
