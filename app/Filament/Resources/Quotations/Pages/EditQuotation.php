<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\Actions\ConvertToOrderAction;
use App\Filament\Resources\Quotations\Actions\DownloadPdfAction;
use App\Filament\Resources\Quotations\Actions\MarkRejectedAction;
use App\Filament\Resources\Quotations\Actions\ViewOrderAction;
use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    public static bool $formActionsAreSticky = true;

    public static string|Alignment $formActionsAlignment = Alignment::End;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            ConvertToOrderAction::make(),
            ViewOrderAction::make(),
            DownloadPdfAction::make(),
            MarkRejectedAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $quotation = $this->getRecord();
        $crewSize = (int) ($quotation->crew_size ?: 4);
        $days = (int) ($quotation->rental_days ?: 1);
        $dist = (float) ($quotation->transport_distance_km ?: 45);

        $data['crew_size'] = $crewSize;
        $data['transport_distance_km'] = $dist;

        if (! empty($quotation->crew_rate) && (float) $quotation->crew_rate > 0) {
            $data['crew_rate'] = (float) $quotation->crew_rate;
        } elseif ($crewSize > 0 && $days > 0 && (float) $quotation->labour_cost > 0) {
            $data['crew_rate'] = round((float) $quotation->labour_cost / ($crewSize * $days));
        } else {
            $data['crew_rate'] = 1600000;
        }

        if (! empty($quotation->transport_rate) && (float) $quotation->transport_rate > 0) {
            $data['transport_rate'] = (float) $quotation->transport_rate;
        } elseif ($dist > 0 && (float) $quotation->transport_cost > 0) {
            $data['transport_rate'] = round((float) $quotation->transport_cost / ($dist * 2));
        } else {
            $data['transport_rate'] = 28000;
        }

        return $data;
    }
}
