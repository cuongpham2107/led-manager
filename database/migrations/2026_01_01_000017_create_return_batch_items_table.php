<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            // Trace back to the exact checkout line this asset was dispatched on
            $table->foreignId('checkout_batch_item_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('grade', ['normal', 'damaged'])->nullable(); // set at scan time
            $table->text('grade_note')->nullable();

            // Resulting asset status after grading: 'ready' (normal) or 'repairing' (damaged)
            $table->boolean('is_received')->default(false);
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();

            $table->unique(['return_batch_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_batch_items');
    }
};
