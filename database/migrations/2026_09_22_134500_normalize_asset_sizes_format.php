<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assets')->where(function ($q) {
            $q->where('size', '0.5×1.0 m')
                ->orWhere('size', '500×1000 mm')
                ->orWhere('size', '1000×500 mm')
                ->orWhere('size', 'like', '%0.5x1%')
                ->orWhere('size', 'like', '%0.5×1%');
        })->update(['size' => '500 x 1000 mm']);

        DB::table('assets')->where(function ($q) {
            $q->where('size', '0.5×0.5 m')
                ->orWhere('size', '500×500 mm')
                ->orWhere('size', 'like', '%0.5x0.5%')
                ->orWhere('size', 'like', '%0.5×0.5%');
        })->update(['size' => '500 x 500 mm']);
    }

    public function down(): void
    {
        // irreversible data normalization
    }
};
