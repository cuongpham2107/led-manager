<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('led_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_line_id')->constrained()->cascadeOnDelete();
            $table->string('name');                   // Tên gọi nội bộ, VD: "Lô 2026-09 Novastar"
            $table->string('receiving_card');         // Card nhận, VD: Novastar A5s Plus
            $table->string('scan_mode');              // Kiểu quét: static | 1/4 | 1/8 | 1/16 | 1/32
            $table->string('controller_model');       // Đầu phát tương thích, VD: Novastar VX600
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_line_id', 'receiving_card', 'scan_mode', 'controller_model'], 'led_config_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('led_configurations');
    }
};
