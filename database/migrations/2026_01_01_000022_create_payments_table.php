<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // PAY-2608-01
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();

            $table->enum('type', ['deposit', 'partial', 'final', 'refund'])->default('deposit');
            $table->enum('method', ['bank_transfer', 'cash', 'other'])->default('bank_transfer');
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->string('reference')->nullable(); // Mã giao dịch ngân hàng / hóa đơn
            $table->text('note')->nullable();

            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
