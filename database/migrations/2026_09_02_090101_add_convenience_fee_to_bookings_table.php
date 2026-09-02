<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Captured at booking time, same pattern as `price` - a later
            // change to the convenience_fee Setting must not retroactively
            // change what a past booking is recorded as having charged.
            // Defaults to 0 so every pre-existing booking (and any
            // deployment that never sets the Setting) reads as no fee at
            // all, not null/undefined.
            $table->decimal('convenience_fee', 8, 2)->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('convenience_fee');
        });
    }
};
