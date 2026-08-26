<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalHeading('Thêm khách hàng mới')
                ->modalDescription('Nhập thông tin liên hệ, doanh nghiệp, mã số thuế và nhóm khách hàng.')
                ->modalWidth(Width::FourExtraLarge),
        ];
    }
}
