<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // QUO-2608-01
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Input for the cost estimator
            $table->decimal('screen_width_m', 8, 2)->nullable();
            $table->decimal('screen_height_m', 8, 2)->nullable();
            $table->decimal('screen_area_m2', 10, 2)->nullable();
            $table->foreignId('product_line_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('rental_days')->nullable();
            $table->date('event_start_date')->nullable();
            $table->date('event_end_date')->nullable();
            $table->string('event_name')->nullable();
            $table->string('location')->nullable();

            // Estimator outputs (cached snapshot)
            $table->unsignedInteger('estimated_cabinet_qty')->nullable();
            $table->unsignedInteger('estimated_processor_qty')->nullable();
            $table->decimal('estimated_load_kg', 10, 2)->nullable();
            $table->decimal('estimated_power_kw', 10, 2)->nullable();

            $table->decimal('equipment_cost', 14, 2)->default(0);
            $table->decimal('labour_cost', 14, 2)->default(0);
            $table->decimal('transport_cost', 14, 2)->default(0);
            $table->decimal('accessory_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);   // COGS sum
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_price', 14, 2)->default(0);  // price quoted to customer
            $table->decimal('margin_percent', 5, 2)->nullable();

            $table->enum('status', [
                'draft', 'sent', 'approved', 'rejected', 'converted', 'expired',
            ])->default('draft');
            $table->string('lost_reason')->nullable(); // giá cao / thiếu SL / đối thủ...

            $table->foreignId('converted_order_id')->nullable(); // FK added in later migration once orders exists

            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
