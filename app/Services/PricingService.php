<?php

namespace App\Services;

use App\Models\PricingRule;
use Illuminate\Support\Facades\Log;

class PricingService
{
    /**
     * Resolve the current unit price for a given product line at a specified date and optional agency.
     *
     * Resolution order:
     * 1. Agency-specific rule matching rental days and effective date
     * 2. Global rule (agency_id IS NULL) matching rental days and effective date
     * 3. Return 0.0 with a warning log (no silent fallback to avoid wrong pricing)
     */
    public function resolveUnitPrice(
        int $productLineId,
        ?string $requestDate = null,
        ?int $agencyId = null,
        ?int $rentalDays = null,
    ): float {
        $targetDate = $requestDate ?: now()->toDateString();

        // 1. Try to find agency-specific pricing rule first if agencyId is provided
        if ($agencyId) {
            $agencyRule = $this->buildPricingQuery($productLineId, $targetDate, $rentalDays)
                ->where('agency_id', $agencyId)
                ->latest('effective_from')
                ->orderBy('min_days', 'asc')
                ->first();

            if ($agencyRule) {
                return (float) $agencyRule->base_price_per_unit_per_day;
            }
        }

        // 2. Find general/global active rule (agency_id is null)
        $globalRule = $this->buildPricingQuery($productLineId, $targetDate, $rentalDays)
            ->whereNull('agency_id')
            ->orderByRaw('CASE WHEN customer_type IS NULL THEN 0 ELSE 1 END ASC')
            ->latest('effective_from')
            ->orderBy('min_days', 'asc')
            ->first();

        if ($globalRule) {
            return (float) $globalRule->base_price_per_unit_per_day;
        }

        // 3. No matching rule — log warning and return 0 instead of silently using any rule
        Log::warning('PricingService: No pricing rule found', [
            'product_line_id' => $productLineId,
            'date' => $targetDate,
            'agency_id' => $agencyId,
            'rental_days' => $rentalDays,
        ]);

        return 0.0;
    }

    /**
     * Build the base pricing query with effective date and rental days filters.
     */
    private function buildPricingQuery(int $productLineId, string $targetDate, ?int $rentalDays = null): mixed
    {
        return PricingRule::query()
            ->where('product_line_id', $productLineId)
            ->effectiveAt($targetDate)
            ->when($rentalDays, fn ($q) => $q
                ->where(fn ($q2) => $q2
                    ->whereNull('min_days')
                    ->orWhere('min_days', '<=', $rentalDays)
                )
                ->where(fn ($q2) => $q2
                    ->whereNull('max_days')
                    ->orWhere('max_days', '>=', $rentalDays)
                )
            );
    }
}
