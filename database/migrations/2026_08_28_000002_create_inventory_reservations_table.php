<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('device_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->enum('lock_type', ['soft', 'hard'])->default('soft');
            $table->date('start_date');
            $table->date('end_date');
            $table->dateTime('expires_at')->nullable();
            $table->enum('status', ['active', 'released', 'converted'])->default('active');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['device_type_id', 'status', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
