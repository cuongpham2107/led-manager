<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phụ lục hợp đồng / thay đổi đơn hàng (Change Order)
        Schema::create('order_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['extend_return', 'add_note', 'add_items', 'price_adjustment'])->default('extend_return');
            $table->text('description')->nullable();
            $table->date('old_expected_return_date')->nullable();
            $table->date('new_expected_return_date')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_amendments');
    }
};
