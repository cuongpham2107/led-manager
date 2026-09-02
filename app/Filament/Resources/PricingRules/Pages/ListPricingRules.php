<?php

namespace App\Filament\Resources\PricingRules\Pages;

use App\Filament\Resources\PricingRules\PricingRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListPricingRules extends ListRecords
{
    protected static string $resource = PricingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->modalHeading('Thêm quy tắc bảng giá thuê')
                ->modalDescription('Thiết lập đơn giá theo ngày, khung thời gian thuê và chính sách chiết khấu theo nhóm khách hàng.')
                ->modalWidth(Width::FiveExtraLarge),
        ];
    }
}
