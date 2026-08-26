<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_line_id')->constrained()->cascadeOnDelete();
            $table->string('customer_type')->nullable(); // individual, agency, corporate — NULL = all
            $table->decimal('base_price_per_unit_per_day', 14, 2); // VND/tấm/ngày
            $table->unsignedInteger('min_days')->default(1);
            $table->unsignedInteger('max_days')->nullable();       // NULL = unlimited
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('crew_rate_per_person_per_day', 14, 2)->default(1600000);
            $table->decimal('transport_rate_per_km', 14, 2)->default(28000);
            $table->decimal('accessory_rate_per_m2', 14, 2)->default(50000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_line_id', 'customer_type', 'is_active'], 'pricing_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
