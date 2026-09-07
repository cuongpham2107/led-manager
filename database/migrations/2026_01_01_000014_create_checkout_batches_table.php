<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Xuất kho — created from "Create outbound batch" on an order.
        Schema::create('checkout_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // OUT-2608-02, auto-generated
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();

            $table->decimal('required_area_m2', 10, 2)->nullable();
            $table->date('export_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->string('purpose')->nullable()->default('Sự kiện');
            $table->text('note')->nullable();

            $table->enum('status', [
                'pending',      // created, no serials picked yet
                'in_progress',  // some serials picked/dispatched
                'dispatched',   // all lines dispatched
                'completed',    // returned and completed
                'cancelled',
            ])->default('pending');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_batches');
    }
};
