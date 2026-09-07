<?php

namespace App\Filament\Resources\RepairLogs\Pages;

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Filament\Resources\RepairLogs\RepairLogResource;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ListRepairLogs extends ListRecords
{
    protected static string $resource = RepairLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->modalHeading('Tạo phiếu bảo trì & sửa chữa')
                ->modalDescription('Ghi nhận thiết bị gặp sự cố, mô tả hư hỏng và chi phí sửa chữa.')
                ->modalWidth(Width::FourExtraLarge)
                ->mutateFormDataUsing(function (array $data): array {
                    $data['result_status'] ??= RepairResultStatus::Pending->value;
                    $data['created_by'] ??= Auth::id();

                    return $data;
                })
                ->after(function (RepairLog $record): void {
                    if ($record->asset && ($record->result_status === RepairResultStatus::Pending || $record->result_status?->value === 'pending')) {
                        $asset = $record->asset;
                        $oldStatus = $asset->current_status;

                        if ($oldStatus !== AssetStatus::Repairing) {
                            $asset->update(['current_status' => AssetStatus::Repairing]);

                            AssetStatusLog::create([
                                'asset_id' => $asset->id,
                                'from_status' => $oldStatus,
                                'to_status' => AssetStatus::Repairing,
                                'from_warehouse_id' => $asset->current_warehouse_id,
                                'to_warehouse_id' => $asset->current_warehouse_id,
                                'source_type' => RepairLog::class,
                                'source_id' => $record->id,
                                'changed_by' => $record->created_by ?: Auth::id(),
                                'note' => 'Chuyển sang bảo dưỡng / sửa chữa: '.($record->repair_note ?: 'Tạo phiếu sửa chữa'),
                                'created_at' => now(),
                            ]);
                        }
                    }
                }),
        ];
    }
}
