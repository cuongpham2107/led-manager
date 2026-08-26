<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkout_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();

            // "a serial already committed to another open batch cannot be picked twice"
            // enforced at application level (check open batches before insert) + this flag
            $table->boolean('is_dispatched')->default(false);
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();

            $table->timestamps();

            $table->unique(['checkout_batch_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_batch_items');
    }
};
