<?php

use App\Enums\InventoryMovementType;
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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default(InventoryMovementType::Adjustment->value);
            // Signed delta, not an absolute count - a Stock In of +20 or a
            // Damaged movement of -3. new_quantity = previous_quantity +
            // quantity always holds (see InventoryService).
            $table->integer('quantity');
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->string('reason')->nullable();
            // Polymorphic-shaped but unused until Store Phase 4, where a
            // completed POS sale's own "sale" movement will point back at
            // it (reference_type = StoreSale::class, reference_id =
            // $sale->id) - not a real polymorphic relation yet, just the
            // two plain columns the spec calls for.
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            // Every movement records who performed it (Store Phase 3
            // spec) - nullOnDelete, not cascade, since a movement's
            // historical record must outlive the user account that made it.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
