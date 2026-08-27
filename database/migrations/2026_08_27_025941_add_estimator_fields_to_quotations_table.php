<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->unsignedInteger('crew_size')->nullable()->default(4)->after('rental_days');
            $table->decimal('transport_distance_km', 8, 2)->nullable()->default(45.0)->after('crew_size');
            $table->decimal('crew_rate', 14, 2)->nullable()->default(1600000)->after('labour_cost');
            $table->decimal('transport_rate', 14, 2)->nullable()->default(28000)->after('transport_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['crew_size', 'transport_distance_km', 'crew_rate', 'transport_rate']);
        });
    }
};
