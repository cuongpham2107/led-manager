<?php

namespace App\Filament\Resources\ReturnBatches\Schemas;

use App\Enums\AssetStatus;
use App\Enums\ReturnGrade;
use App\Models\ReturnBatch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ReturnBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        TextInput::make('code')
                            ->label('Mã đợt trả')
                            ->disabled()
                            ->dehydrated(),

                        Select::make('checkout_batch_id')
                            ->label('Theo đợt xuất kho')
                            ->relationship('checkoutBatch', 'code')
                            ->disabled()
                            ->dehydrated(),

                        DatePicker::make('return_date')
                            ->label('Ngày trả thực tế')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now()->toDateString()),
                    ])
                    ->columnSpanFull(),

                Textarea::make('note')
                    ->label('Ghi chú')
                    ->placeholder('Ghi nhận chung tình trạng thiết bị khi thu hồi...')
                    ->columnSpanFull(),

                ViewField::make('selected_assets')
                    ->label('Danh sách thiết bị hoàn trả')
                    ->view('filament.components.return-batch-assets-selector')
                    ->viewData(function (?ReturnBatch $record = null) {
                        $initialAssets = [];

                        if ($record instanceof ReturnBatch) {
                            $initialAssets = $record->items()
                                ->with('asset.productLine')
                                ->get()
                                ->map(function ($item) {
                                    $asset = $item->asset;
                                    if (! $asset) {
                                        return null;
                                    }
                                    $statusLabel = $asset->current_status instanceof AssetStatus
                                        ? $asset->current_status->getLabel()
                                        : 'Sẵn sàng trong kho';
                                    $statusColor = $asset->current_status instanceof AssetStatus
                                        ? $asset->current_status->getColor()
                                        : 'success';

                                    $condition = null;
                                    if ($item->grade === ReturnGrade::Damaged || $item->grade?->value === 'damaged') {
                                        $condition = 'damaged';
                                    } elseif ($item->grade === ReturnGrade::Normal || $item->grade?->value === 'normal') {
                                        $condition = 'normal';
                                    }

                                    return [
                                        'id' => (int) $asset->id,
                                        'item_id' => (int) $item->id,
                                        'serial_no' => (string) $asset->serial_no,
                                        'name' => (string) ($asset->productLine?->name ?? 'LED'),
                                        'size' => (string) ($asset->size ?? '0.5×0.5 m'),
                                        'status' => (string) $statusLabel,
                                        'status_color' => (string) $statusColor,
                                        'is_received' => (bool) $item->is_received,
                                        'condition' => $condition,
                                        'condition_raw' => $item->grade?->value ?? ($item->is_received ? 'normal' : null),
                                        'grade_note' => $item->grade_note,
                                        'received_at' => $item->received_at ? $item->received_at->format('d/m/Y H:i') : null,
                                    ];
                                })
                                ->filter()
                                ->values()
                                ->toArray();
                        }

                        return [
                            'initialAssets' => $initialAssets,
                            'batchId' => $record?->id,
                            'batchStatus' => $record?->status?->value ?? 'pending',
                        ];
                    })
                    ->columnSpanFull(),
            ]);
    }
}
