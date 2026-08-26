<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('serial_no')->unique();   // scanned QR/barcode value
            $table->string('qr_code')->nullable()->unique();

            $table->foreignId('product_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_type_id')->constrained()->restrictOnDelete();

            $table->string('size')->nullable();       // 500x500mm
            $table->date('manufactured_date')->nullable();
            $table->decimal('purchase_cost', 14, 2)->nullable();
            $table->date('purchase_date')->nullable();

            // Current state — denormalized for fast lookup; source of truth is asset_status_logs
            $table->enum('current_status', [
                'ready',        // Trong kho / Sẵn sàng
                'in_event',     // Đang đi sự kiện
                'in_transit',   // Đang vận chuyển
                'repairing',    // Đang bảo trì/sửa chữa
                'disposed',     // Đã hỏng/Thanh lý
            ])->default('ready');

            $table->foreignId('current_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_line_id', 'current_status']);
            $table->index(['current_warehouse_id', 'current_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
