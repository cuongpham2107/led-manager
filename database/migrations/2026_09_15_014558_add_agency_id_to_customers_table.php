<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'agency_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('agency_id')->nullable()->after('type')->constrained('agencies')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('customers', 'created_by')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('agency_id')->constrained('users')->nullOnDelete();
            });
        }

        // Backfill agency_id for customers associated with agency orders
        $agencyOrders = DB::table('orders')->whereNotNull('agency_id')->select('customer_id', 'agency_id', 'sales_user_id')->get();
        foreach ($agencyOrders as $order) {
            DB::table('customers')->where('id', $order->customer_id)->update([
                'agency_id' => $order->agency_id,
                'created_by' => $order->sales_user_id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('customers', 'created_by')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        if (Schema::hasColumn('customers', 'agency_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('agency_id');
            });
        }
    }
};
