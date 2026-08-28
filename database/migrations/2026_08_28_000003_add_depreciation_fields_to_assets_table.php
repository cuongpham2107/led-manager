<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('accumulated_depreciation', 14, 2)->default(0)->after('purchase_cost');
            $table->unsignedInteger('useful_life_months')->default(36)->after('accumulated_depreciation');
            $table->string('depreciation_method')->default('straight_line')->after('useful_life_months');
            $table->decimal('salvage_value', 14, 2)->default(0)->after('depreciation_method');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'accumulated_depreciation',
                'useful_life_months',
                'depreciation_method',
                'salvage_value',
            ]);
        });
    }
};
