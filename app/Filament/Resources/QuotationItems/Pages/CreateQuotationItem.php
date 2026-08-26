<?php

namespace App\Filament\Resources\QuotationItems\Pages;

use App\Filament\Resources\QuotationItems\QuotationItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotationItem extends CreateRecord
{
    protected static string $resource = QuotationItemResource::class;
}
