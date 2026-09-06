<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique(); // ORD-2608-01
            $table->text('note')->nullable();

            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();

            $table->date('request_date');
            $table->date('expected_return_date')->nullable();

            $table->decimal('area_m2', 10, 2)->nullable();
            $table->string('event')->nullable(); // event name/description
            $table->foreignId('product_line_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('value', 14, 2)->default(0); // contract/order value

            // Payment snapshot (số tiền đã thu được nhập tay — không còn bảng payments)
            $table->decimal('deposit_paid', 14, 2)->default(0);
            $table->decimal('total_paid', 14, 2)->default(0);
            $table->timestamp('paid_at')->nullable();

            $table->enum('status', [
                'draft',            // New orders start here
                'outbound_created', // "Create outbound batch" moved it into warehouse workflow
                'dispatched',       // fully checked out
                'returned',         // fully returned
                'completed',
                'cancelled',
            ])->default('draft');

            $table->foreignId('sales_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
