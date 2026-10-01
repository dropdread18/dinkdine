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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            // What the tournament is called - plays the same role
            // customer_name does on training_sessions, just not tied to
            // one named person.
            $table->string('tournament_name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('notes')->nullable();
            $table->string('reclub_link')->nullable();
            $table->timestamps();

            // Same shape as training_sessions'/open_play_sessions' index -
            // AvailabilityService looks these up per court/date the same way.
            $table->index(['court_id', 'session_date', 'start_time', 'end_time'], 'tournaments_availability_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
