<?php

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 8, 2);
            $table->string('status')->default(BookingStatus::Pending->value);
            $table->string('payment_status')->default(PaymentStatus::Unpaid->value);
            $table->string('source')->default(BookingSource::Online->value);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Supports the availability engine's "is this court free at this
            // time" query (Requirements.md §47: court_id/date/time/status).
            $table->index(['court_id', 'booking_date', 'start_time', 'end_time', 'status'], 'bookings_availability_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
