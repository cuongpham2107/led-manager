<?php

namespace App\Services;

use App\Models\PricingRule;

class PricingService
{
    /**
     * Resolve the current unit price for a given product line at a specified date and optional agency.
     */
    public function resolveUnitPrice(int $productLineId, ?string $requestDate = null, ?int $agencyId = null): float
    {
        $targetDate = $requestDate ?: now()->toDateString();

        // 1. Try to find agency-specific pricing rule first if agencyId is provided
        if ($agencyId) {
            $agencyRule = PricingRule::query()
                ->where('product_line_id', $productLineId)
                ->where('agency_id', $agencyId)
                ->effectiveAt($targetDate)
                ->orderBy('min_days', 'asc')
                ->latest('effective_from')
                ->first();

            if ($agencyRule) {
                return (float) $agencyRule->base_price_per_unit_per_day;
            }
        }

        // 2. Find general/global active rule (agency_id is null)
        $globalRule = PricingRule::query()
            ->where('product_line_id', $productLineId)
            ->whereNull('agency_id')
            ->effectiveAt($targetDate)
            ->orderByRaw('CASE WHEN customer_type IS NULL THEN 0 ELSE 1 END')
            ->orderBy('min_days', 'asc')
            ->latest('effective_from')
            ->first();

        if ($globalRule) {
            return (float) $globalRule->base_price_per_unit_per_day;
        }

        // 3. Fallback: Any active rule for this product line
        $fallbackRule = PricingRule::query()
            ->where('product_line_id', $productLineId)
            ->where('is_active', true)
            ->first();

        return (float) ($fallbackRule?->base_price_per_unit_per_day ?? 0.0);
    }
}
