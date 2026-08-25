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
        Schema::create('open_play_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Same shape as court_maintenance's index - AvailabilityService
            // looks these up per court/date the same way.
            $table->index(['court_id', 'session_date', 'start_time', 'end_time'], 'open_play_sessions_availability_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_play_sessions');
    }
};
