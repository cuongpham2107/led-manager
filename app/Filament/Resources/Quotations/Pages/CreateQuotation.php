<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Services\LedCalculationService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected static ?string $title = 'Tạo Mới Báo Giá & Dự Toán Màn Hình LED';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sales_user_id'] = $data['sales_user_id'] ?? Auth::id();

        return $data;
    }

    protected function fillForm(): void
    {
        parent::fillForm();

        $defaultPl = ProductLine::where('code', 'P2.6')->first() ?? ProductLine::first();
        $service = app(LedCalculationService::class);
        $config = $service->deriveConfiguration(6.0, 3.5, $defaultPl);
        $bom = $service->generateBom(6.0, 3.5, $defaultPl, 3);
        $pricing = $service->calculatePricing(6.0, 3.5, $defaultPl, 3, 4, 45.0, 0.0);

        $items = [];
        foreach ($bom as $item) {
            $items[] = [
                'device_type_id' => $item['device_type_id'],
                'description' => $item['item'],
                'quantity' => $item['qty'],
                'unit_cost' => $item['unit_cost'],
                'line_total' => $item['line_total'],
            ];
        }

        $this->form->fill([
            'code' => 'QUO-'.date('ym').'-'.str_pad((string) (Quotation::count() + 1), 2, '0', STR_PAD_LEFT),
            'screen_width_m' => 6.0,
            'screen_height_m' => 3.5,
            'product_line_id' => $defaultPl?->id,
            'rental_days' => 3,
            'crew_size' => 4,
            'transport_distance_km' => 45,
            'screen_area_m2' => $config['wall_area'],
            'estimated_cabinet_qty' => $config['cabinets_qty'],
            'estimated_processor_qty' => 2,
            'estimated_load_kg' => $config['load_kg'],
            'estimated_power_kw' => $config['peak_power_kw'],
            'items' => $items,
            'equipment_cost' => $pricing['equipment_rental'],
            'crew_rate' => 1600000,
            'labour_cost' => $pricing['crew_labour'],
            'transport_rate' => 28000,
            'transport_cost' => $pricing['transport'],
            'accessory_cost' => $pricing['accessory'],
            'total_cost' => $pricing['total_cost'],
            'discount_amount' => 0,
            'total_price' => $pricing['total_price'],
            'margin_percent' => $pricing['margin_percent'],
        ]);
    }
}
