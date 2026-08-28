<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CodeGeneratorService
{
    /**
     * Generate a unique sequential code with prefix and period (YYMM).
     *
     * Example: generate('HD', 'contracts') → 'HD-2608-0001'
     *
     * Uses a DB transaction + row-level lock to prevent race conditions, and
     * computes the next sequence in PHP so mixed-width existing codes (e.g.
     * 'HD-2608-09' vs 'HD-2608-0010') never collide.
     *
     * @param  string  $prefix  Code prefix (e.g. 'HD', 'ORD', 'OUT', 'RET', 'PAY')
     * @param  string  $table  Database table to query last sequence from
     * @param  int  $digits  Zero-padded sequence length (default 4)
     * @return string Generated code
     */
    public static function generate(string $prefix, string $table, int $digits = 4): string
    {
        return DB::transaction(function () use ($prefix, $table, $digits) {
            $period = date('ym');
            $pattern = "{$prefix}-{$period}-%";

            // Lock the matching rows and compute the max numeric sequence in PHP
            // (lexicographic ORDER BY is unreliable across mixed digit widths).
            $existingCodes = DB::table($table)
                ->where('code', 'like', $pattern)
                ->lockForUpdate()
                ->pluck('code');

            $sequence = 0;

            foreach ($existingCodes as $code) {
                $segment = (int) substr($code, strrpos($code, '-') + 1);
                if ($segment > $sequence) {
                    $sequence = $segment;
                }
            }

            $sequence++;

            return sprintf('%s-%s-%s', $prefix, $period, str_pad((string) $sequence, $digits, '0', STR_PAD_LEFT));
        });
    }
}
