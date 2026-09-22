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
        Schema::create('store_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_sale_id')->constrained()->cascadeOnDelete();
            // nullOnDelete, not restrict/cascade - a product is never
            // hard-deleted in this app (deactivate only), but this receipt
            // line must never depend on the product row still existing.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            // product_name/unit_price/cost_price are a snapshot taken at
            // sale time, never re-read from the live product later - a
            // price change (or the product being renamed/deactivated)
            // must never alter what an old receipt shows (Store Phase 4
            // spec: "do not calculate historical receipts using the
            // current product price").
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('cost_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_sale_items');
    }
};
