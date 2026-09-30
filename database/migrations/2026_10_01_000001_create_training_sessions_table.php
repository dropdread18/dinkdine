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
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            // Who the coaching/drill session is for - shown on the grid to
            // staff/admin only (same DEC-023 rule that gates a booking's
            // customer name from the Organizer role), never a full
            // customer account since this is an admin-blocked slot, not a
            // self-service booking.
            $table->string('customer_name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Same shape as open_play_sessions' index - AvailabilityService
            // looks these up per court/date the same way.
            $table->index(['court_id', 'session_date', 'start_time', 'end_time'], 'training_sessions_availability_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
