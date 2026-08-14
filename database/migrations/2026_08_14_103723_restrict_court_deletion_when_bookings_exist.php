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
        // Building Court Management surfaced that court_id cascaded on
        // delete: removing a court would silently destroy its booking
        // (and financial) history. A court with bookings must be
        // deactivated, not deleted. user_id still cascades - deleting
        // users isn't built yet, revisit when it is.
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['court_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('court_id')->references('id')->on('courts')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['court_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('court_id')->references('id')->on('courts')->cascadeOnDelete();
        });
    }
};
