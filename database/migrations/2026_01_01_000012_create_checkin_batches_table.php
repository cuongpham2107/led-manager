<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nhập kho — e.g. new stock arriving into a warehouse (not tied to a prior outbound).
        Schema::create('checkin_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // IN-2608-03, auto-generated
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->text('note')->nullable();
            $table->date('expected_date')->nullable();

            $table->enum('status', [
                'pending',      // created, no serials received yet
                'in_progress',  // some serials received
                'completed',    // all lines received
                'cancelled',
            ])->default('pending');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_batches');
    }
};
