<?php

use App\Enums\StoreSaleStatus;
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
        Schema::create('store_sales', function (Blueprint $table) {
            $table->id();
            // Human-readable (SALE-000001) but derived from this row's own
            // auto-increment id right after insert (see StoreSaleService) -
            // never a racy MAX()+1 read, so it's unique even under
            // concurrent checkouts without any extra locking.
            $table->string('sale_number')->unique();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('payment_method');
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('change_amount', 10, 2);
            // Who rang up the sale - nullOnDelete, not cascade, since a
            // sale's own historical record must outlive the user account
            // that made it (same reasoning as inventory_movements.user_id).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default(StoreSaleStatus::Completed->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_sales');
    }
};
