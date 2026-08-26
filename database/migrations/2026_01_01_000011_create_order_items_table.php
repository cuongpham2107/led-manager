<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What the order requires — not yet tied to specific serials (that happens at checkout).
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity_required');
            $table->decimal('unit_price', 14, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
