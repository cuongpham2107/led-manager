<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $quotation = $this->getRecord();
        $crewSize = (int) ($quotation->crew_size ?: 4);
        $days = (int) ($quotation->rental_days ?: 1);
        $dist = (float) ($quotation->transport_distance_km ?: 45);

        if ($crewSize > 0 && $days > 0 && (float) $quotation->labour_cost > 0) {
            $data['crew_rate'] = round((float) $quotation->labour_cost / ($crewSize * $days));
        } else {
            $data['crew_rate'] = 1600000;
        }

        if ($dist > 0 && (float) $quotation->transport_cost > 0) {
            $data['transport_rate'] = round((float) $quotation->transport_cost / ($dist * 2));
        } else {
            $data['transport_rate'] = 28000;
        }

        return $data;
    }
}
