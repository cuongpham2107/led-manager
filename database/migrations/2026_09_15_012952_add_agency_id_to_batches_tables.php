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
        if (! Schema::hasColumn('checkin_batches', 'agency_id')) {
            Schema::table('checkin_batches', function (Blueprint $table) {
                $table->foreignId('agency_id')->nullable()->after('warehouse_id')->constrained('agencies')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('checkout_batches', 'agency_id')) {
            Schema::table('checkout_batches', function (Blueprint $table) {
                $table->foreignId('agency_id')->nullable()->after('warehouse_id')->constrained('agencies')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('return_batches', 'agency_id')) {
            Schema::table('return_batches', function (Blueprint $table) {
                $table->foreignId('agency_id')->nullable()->after('checkout_batch_id')->constrained('agencies')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('return_batches', 'warehouse_id')) {
            Schema::table('return_batches', function (Blueprint $table) {
                $table->foreignId('warehouse_id')->nullable()->after('agency_id')->constrained('warehouses')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('repair_logs', 'agency_id')) {
            Schema::table('repair_logs', function (Blueprint $table) {
                $table->foreignId('agency_id')->nullable()->after('asset_id')->constrained('agencies')->nullOnDelete();
            });
        }

        // Backfill existing checkin_batches & checkout_batches
        $agencyWarehouses = DB::table('agencies')->whereNotNull('warehouse_id')->pluck('id', 'warehouse_id');
        foreach ($agencyWarehouses as $warehouseId => $agencyId) {
            DB::table('checkin_batches')->where('warehouse_id', $warehouseId)->update(['agency_id' => $agencyId]);
            DB::table('checkout_batches')->where('warehouse_id', $warehouseId)->update(['agency_id' => $agencyId]);
        }

        // Backfill return_batches
        $checkoutBatches = DB::table('checkout_batches')->select('id', 'agency_id', 'warehouse_id')->get();
        foreach ($checkoutBatches as $cb) {
            DB::table('return_batches')->where('checkout_batch_id', $cb->id)->update([
                'agency_id' => $cb->agency_id,
                'warehouse_id' => $cb->warehouse_id,
            ]);
        }

        // Backfill repair_logs
        $assets = DB::table('assets')->select('id', 'current_warehouse_id')->whereNotNull('current_warehouse_id')->get();
        foreach ($assets as $asset) {
            if (isset($agencyWarehouses[$asset->current_warehouse_id])) {
                DB::table('repair_logs')->where('asset_id', $asset->id)->update([
                    'agency_id' => $agencyWarehouses[$asset->current_warehouse_id],
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('repair_logs', 'agency_id')) {
            Schema::table('repair_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('agency_id');
            });
        }

        if (Schema::hasColumn('return_batches', 'warehouse_id')) {
            Schema::table('return_batches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }

        if (Schema::hasColumn('return_batches', 'agency_id')) {
            Schema::table('return_batches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('agency_id');
            });
        }

        if (Schema::hasColumn('checkout_batches', 'agency_id')) {
            Schema::table('checkout_batches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('agency_id');
            });
        }

        if (Schema::hasColumn('checkin_batches', 'agency_id')) {
            Schema::table('checkin_batches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('agency_id');
            });
        }
    }
};
