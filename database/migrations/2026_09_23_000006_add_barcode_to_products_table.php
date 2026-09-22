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
        Schema::table('products', function (Blueprint $table) {
            // Optional - not every product has a printed barcode. Distinct
            // from `sku` (an internal code this app assigns) - this is the
            // real UPC/EAN already printed on a supplier's packaging (soda,
            // chips, etc.), scanned via the camera on the POS grid.
            $table->string('barcode')->nullable()->unique()->after('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }
};
