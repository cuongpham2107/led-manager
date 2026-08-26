<?php

namespace App\Filament\Resources\CheckinBatchItems\Pages;

use App\Filament\Resources\CheckinBatchItems\CheckinBatchItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCheckinBatchItem extends CreateRecord
{
    protected static string $resource = CheckinBatchItemResource::class;
}
