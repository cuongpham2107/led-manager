<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nhập trả — always tied to a specific outbound batch.
        Schema::create('return_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // RET-2608-01
            $table->foreignId('checkout_batch_id')->constrained()->restrictOnDelete();
            $table->date('return_date')->nullable();
            $table->text('note')->nullable();

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_batches');
    }
};
