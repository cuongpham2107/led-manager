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

            $table->boolean('is_dispatched')->default(false);
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['checkout_batch_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_batch_items');
    }
};
