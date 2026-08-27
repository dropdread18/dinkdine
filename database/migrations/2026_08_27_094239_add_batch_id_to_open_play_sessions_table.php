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
        Schema::table('open_play_sessions', function (Blueprint $table) {
            // Ties together every court-row a single "Schedule Open Play"
            // submission created (one row per selected court, same batch_id
            // across all of them) - lets the grid tell two DIFFERENT Open
            // Play events on the same day apart from two rows that are
            // really just the same event running on multiple courts.
            $table->string('batch_id', 36)->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_play_sessions', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });
    }
};
