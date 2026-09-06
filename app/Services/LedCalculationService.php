<?php

namespace App\Services;

use App\Models\PricingRule;
use App\Models\ProductLine;

class LedCalculationService
{
    /**
     * @return array{
     *     wall_area: float,
     *     grid_cols: int,
     *     grid_rows: int,
     *     grid_display: string,
     *     cabinets_qty: int,
     *     resolution_w: int,
     *     resolution_h: int,
     *     resolution_display: string,
     *     load_kg: float,
     *     peak_power_kw: float,
     *     flight_cases: int
     * }
     */
    public function deriveConfiguration(float $width, float $height, ?ProductLine $productLine = null): array
    {
        $area = round($width * $height, 2);

        $moduleW = $productLine ? ($productLine->module_width_mm / 1000) : 0.5;
        $moduleH = $productLine ? ($productLine->module_height_mm / 1000) : 0.5;

        $gridCols = max(1, (int) round($width / $moduleW));
        $gridRows = max(1, (int) round($height / $moduleH));
        $cabinetsQty = $gridCols * $gridRows;

        $pitch = $productLine ? $productLine->pixel_pitch : 2.6;
        $resW = (int) round(($width * 1000) / $pitch);
        $resH = (int) round(($height * 1000) / $pitch);

        $weightPerCab = $productLine ? $productLine->weight_kg : 6.8;
        $powerPerCab = $productLine ? $productLine->power_watt : 380;

        $loadKg = round(($cabinetsQty * $weightPerCab) + ($gridCols * 5), 1);
        $peakPowerKw = round(($cabinetsQty * $powerPerCab) / 1000, 1);
        $flightCases = (int) ceil($cabinetsQty / 6);

        return [
            'wall_area' => $area,
            'grid_cols' => $gridCols,
            'grid_rows' => $gridRows,
            'grid_display' => "{$gridCols} × {$gridRows}",
            'cabinets_qty' => $cabinetsQty,
            'resolution_w' => $resW,
            'resolution_h' => $resH,
            'resolution_display' => "{$resW} × {$resH}",
            'load_kg' => $loadKg,
            'peak_power_kw' => $peakPowerKw,
            'flight_cases' => $flightCases,
        ];
    }

    /**
     * Resolve the effective pricing rates for a given product line and rental days.
     *
     * @return array{
     *     base_price: float,
     *     crew_rate: float,
     *     transport_rate: float,
     *     accessory_rate: float,
     *     discount_percent: float
     * }
     */
    public function resolvePricing(?ProductLine $productLine, int $rentalDays = 1, ?string $customerType = null): array
    {
        $defaults = [
            'base_price' => 400000.0,
            'crew_rate' => 1600000.0,
            'transport_rate' => 28000.0,
            'accessory_rate' => 50000.0,
            'discount_percent' => 0.0,
        ];

        if (! $productLine) {
            return $defaults;
        }

        // Find the best matching pricing rule for this product line + rental days
        $rule = PricingRule::where('product_line_id', $productLine->id)
            ->where('is_active', true)
            ->where('min_days', '<=', $rentalDays)
            ->where(function ($q) use ($rentalDays) {
                $q->whereNull('max_days')
                    ->orWhere('max_days', '>=', $rentalDays);
            })
            ->when($customerType, function ($q) use ($customerType) {
                $q->where(function ($sub) use ($customerType) {
                    $sub->where('customer_type', $customerType)
                        ->orWhereNull('customer_type');
                })->orderByRaw('customer_type IS NULL ASC'); // prioritize specific match
            }, function ($q) {
                $q->whereNull('customer_type');
            })
            ->orderBy('min_days', 'desc') // most specific day range first
            ->first();

        if (! $rule) {
            return $defaults;
        }

        return [
            'base_price' => (float) $rule->base_price_per_unit_per_day,
            'crew_rate' => (float) $rule->crew_rate_per_person_per_day,
            'transport_rate' => (float) $rule->transport_rate_per_km,
            'accessory_rate' => (float) $rule->accessory_rate_per_m2,
            'discount_percent' => (float) $rule->discount_percent,
        ];
    }

    /**
     * @return array<int, array{
     *     item: string,
     *     qty: int,
     *     product_line_id: ?int,
     *     unit_cost: float,
     *     line_total: float
     * }>
     */
    public function generateBom(
        float $width,
        float $height,
        ?ProductLine $productLine = null,
        int $rentalDays = 1,
        ?string $customerType = null
    ): array {
        $config = $this->deriveConfiguration($width, $height, $productLine);
        $cabs = $config['cabinets_qty'];

        $wMm = $productLine ? (int) $productLine->module_width_mm : 500;
        $hMm = $productLine ? (int) $productLine->module_height_mm : 500;

        // Dynamic pricing lookup
        $rates = $this->resolvePricing($productLine, $rentalDays, $customerType);
        $cabUnitCost = $rates['base_price'] * $rentalDays;
        $dayRateFmt = number_format($rates['base_price'], 0, ',', '.');
        $descNote = $rentalDays > 1 ? " ({$dayRateFmt} đ/ngày × {$rentalDays} ngày)" : '';

        return [
            [
                'item' => "Cabinet LED {$productLine?->name} ({$wMm}×{$hMm}mm){$descNote}",
                'qty' => $cabs,
                'product_line_id' => $productLine?->id,
                'unit_cost' => $cabUnitCost,
                'line_total' => $cabs * $cabUnitCost,
            ],
        ];
    }

    /**
     * @return array{
     *     equipment_rental: float,
     *     crew_labour: float,
     *     transport: float,
     *     accessory: float,
     *     total_cost: float,
     *     discount: float,
     *     total_price: float,
     *     margin_percent: float
     * }
     */
    public function calculatePricing(
        float $width,
        float $height,
        ?ProductLine $productLine = null,
        int $rentalDays = 1,
        int $crewSize = 2,
        float $transportDistanceKm = 30.0,
        float $discount = 0.0,
        ?string $customerType = null
    ): array {
        $config = $this->deriveConfiguration($width, $height, $productLine);
        $cabs = $config['cabinets_qty'];
        $area = $config['wall_area'];

        // Dynamic pricing lookup
        $rates = $this->resolvePricing($productLine, $rentalDays, $customerType);

        $equipmentRental = $cabs * $rates['base_price'] * $rentalDays;
        $crewLabour = $crewSize * $rates['crew_rate'] * $rentalDays;
        $transport = ($transportDistanceKm * 2) * $rates['transport_rate'];
        $accessory = $area * $rates['accessory_rate'];

        $totalCost = ($equipmentRental * 0.40) + ($crewLabour * 0.70) + ($transport * 0.60) + $accessory;
        $rawPrice = $equipmentRental + $crewLabour + $transport + $accessory;
        $totalPrice = max(0, $rawPrice - $discount);

        $marginPercent = $totalPrice > 0 ? round((($totalPrice - $totalCost) / $totalPrice) * 100, 1) : 0.0;

        return [
            'equipment_rental' => $equipmentRental,
            'crew_labour' => $crewLabour,
            'transport' => $transport,
            'accessory' => $accessory,
            'total_cost' => $totalCost,
            'discount' => $discount,
            'total_price' => $totalPrice,
            'margin_percent' => $marginPercent,
        ];
    }
}
