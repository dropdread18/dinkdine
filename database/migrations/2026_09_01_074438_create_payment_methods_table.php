<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('qr_code_path');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Facilities that already uploaded a single QR code via Settings
        // (the "GCash only" setup this table replaces) shouldn't lose it -
        // carry it over as their first payment method instead of forcing
        // them to re-upload it. Raw DB queries, not Eloquent, since this
        // Setting row (and its underlying table) may not exist in every
        // environment running this migration and a model class is more
        // likely to change shape over time than a migration should depend on.
        $existingQrPath = DB::table('settings')->where('key', 'payment_qr_code')->value('value');

        if (! empty($existingQrPath)) {
            DB::table('payment_methods')->insert([
                'name' => 'GCash',
                'qr_code_path' => $existingQrPath,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
