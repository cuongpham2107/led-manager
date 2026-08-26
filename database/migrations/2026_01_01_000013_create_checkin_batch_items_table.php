<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkin_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkin_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();

            $table->enum('condition', ['ok', 'fault'])->nullable(); // set at scan time (PDA: OK / Fault)
            $table->text('condition_note')->nullable();

            $table->boolean('is_received')->default(false);
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();

            $table->unique(['checkin_batch_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_batch_items');
    }
};
