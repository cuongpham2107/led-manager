<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable(); // CUS-0001
            $table->enum('type', ['individual', 'agency', 'corporate'])->default('individual');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_code')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
