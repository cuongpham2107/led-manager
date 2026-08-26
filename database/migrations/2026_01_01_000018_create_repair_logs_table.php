<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable(); // NULL end_date => asset shows "Repairing"

            $table->text('repair_note')->nullable();
            $table->enum('result_status', ['pending', 'fixed', 'disposed'])->nullable();
            $table->decimal('repair_cost', 14, 2)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_id', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_logs');
    }
};
