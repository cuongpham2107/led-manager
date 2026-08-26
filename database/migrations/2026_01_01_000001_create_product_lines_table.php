<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_lines', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // P2.5 Indoor, P3.91 Outdoor...
            $table->string('code')->unique();        // P25-IN, P391-OUT
            $table->enum('pixel_pitch_unit', ['mm'])->default('mm');
            $table->decimal('pixel_pitch', 5, 2)->nullable(); // 2.5, 3.91, 4.81
            $table->enum('environment', ['indoor', 'outdoor', 'both'])->default('indoor');
            $table->decimal('module_width_mm', 8, 2)->nullable();
            $table->decimal('module_height_mm', 8, 2)->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('power_watt', 8, 2)->nullable();
            $table->string('brand')->nullable();      // Novastar, Colorlight...
            $table->string('cabinet_material')->nullable(); // nhôm đúc, sắt
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_lines');
    }
};
