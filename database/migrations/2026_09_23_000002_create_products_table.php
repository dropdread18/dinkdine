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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Nullable + nullOnDelete rather than a hard-required FK -
            // categories are never hard-deleted in this app (deactivate
            // only, see product_categories.is_active), but a product
            // should never become unreachable/unbookable in the unlikely
            // event a category row is ever removed by hand.
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            // Fixed-point, not float - see PricingService/Payment amount
            // columns elsewhere in this app for the same reasoning: money
            // must never go through floating-point arithmetic.
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->string('unit')->default('piece');
            $table->integer('stock_quantity')->default(0);
            $table->integer('minimum_stock')->default(0);
            // Deactivate, never hard-delete - a product used in a completed
            // store sale must keep resolving on that historical receipt
            // (Store Phase 4/5 spec). Only removed from the active POS
            // catalog.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
