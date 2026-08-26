<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Category of physical equipment: Cabinet, Processor, Flycase, Truss, Cable...
        Schema::create('device_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('unit', ['piece', 'set', 'meter', 'roll'])->default('piece');
            $table->boolean('requires_serial')->default(true); // false for bulk items like cable
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_types');
    }
};
