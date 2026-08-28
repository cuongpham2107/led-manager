<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Models\Asset;

class DepreciationService
{
    /**
     * Calculate straight-line monthly depreciation for an asset
     */
    public function calculateMonthlyDepreciation(Asset $asset): float
    {
        return $asset->monthly_depreciation;
    }

    /**
     * Get the current remaining book value
     */
    public function getCurrentBookValue(Asset $asset): float
    {
        return $asset->current_book_value;
    }

    /**
     * Process one month of depreciation for all active assets
     */
    public function processMonthlyDepreciation(): int
    {
        $assets = Asset::where('current_status', '!=', AssetStatus::Disposed)
            ->whereNotNull('purchase_cost')
            ->where('purchase_cost', '>', 0)
            ->get();

        $processedCount = 0;

        foreach ($assets as $asset) {
            $cost = (float) $asset->purchase_cost;
            $salvage = (float) ($asset->salvage_value ?? 0);
            $maxDep = max(0, $cost - $salvage);
            $currentAcc = (float) ($asset->accumulated_depreciation ?? 0);

            if ($currentAcc >= $maxDep) {
                continue;
            }

            $monthly = $asset->monthly_depreciation;
            if ($monthly <= 0) {
                continue;
            }

            $newAcc = min($maxDep, $currentAcc + $monthly);
            $asset->update(['accumulated_depreciation' => $newAcc]);
            $processedCount++;
        }

        return $processedCount;
    }
}
