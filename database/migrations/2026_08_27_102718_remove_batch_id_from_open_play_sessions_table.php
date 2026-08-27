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
            // Turned out unnecessary: grouping Open Play slots by date+time
            // window (computed at read time from columns that already
            // existed) correctly handles "same event, multiple courts"
            // whether it was scheduled in one multi-court submission or as
            // separate single-court ones - batch_id only ever captured the
            // former, and was null for every session that predated it.
            $table->dropColumn('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_play_sessions', function (Blueprint $table) {
            $table->string('batch_id', 36)->nullable()->after('created_by');
        });
    }
};
