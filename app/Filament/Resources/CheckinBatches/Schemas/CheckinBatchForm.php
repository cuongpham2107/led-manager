<?php

namespace App\Filament\Resources\CheckinBatches\Schemas;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Models\CheckinBatch;
use App\Models\ProductLine;
use App\Services\CodeGeneratorService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CheckinBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Mã đợt')
                    ->default(fn () => CodeGeneratorService::generate('IN', 'checkin_batches'))
                    ->required()
                    ->placeholder('VD: IN-2608-01')
                    ->columnSpanFull(),

                TextInput::make('note')
                    ->label('Ghi chú')
                    ->placeholder('Thu hồi sau sự kiện ABC Corp / XYZ / Rex Hotel')
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        Select::make('warehouse_id')
                            ->label('Kho hàng')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),

                        DatePicker::make('expected_date')
                            ->label('Ngày dự kiến')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->placeholder('DD/MM/YYYY'),
                    ])
                    ->extraAttributes(['class' => 'relative z-30', 'style' => 'position: relative; z-index: 30;'])
                    ->columnSpanFull(),

                ViewField::make('selected_assets')
                    ->label('Mã hàng trong đợt')
                    ->extraAttributes(['class' => 'relative z-0', 'style' => 'position: relative; z-index: 0;'])
                    ->view('filament.components.checkin-batch-assets-selector')
                    ->viewData(function (Get $get, ?CheckinBatch $record = null, string $operation = 'create') {
                        $isEdit = ($record instanceof CheckinBatch && $record->exists) || $operation === 'edit';
                        $initialAssets = [];

                        if ($record instanceof CheckinBatch) {
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
                                    if ($item->condition === 'fault') {
                                        $condition = 'damaged';
                                    } elseif ($item->condition === 'ok') {
                                        $condition = 'normal';
                                    }

                                    return [
                                        'id' => (int) $asset->id,
                                        'item_id' => (int) $item->id,
                                        'serial_no' => (string) $asset->serial_no,
                                        'product_line_id' => (int) $asset->product_line_id,
                                        'name' => (string) ($asset->productLine?->name ?? 'LED'),
                                        'size' => (string) ($asset->size ?? '0.5×0.5 m'),
                                        'status' => (string) $statusLabel,
                                        'status_raw' => (string) ($asset->current_status instanceof AssetStatus ? $asset->current_status->value : $asset->current_status),
                                        'status_color' => (string) $statusColor,
                                        'is_received' => (bool) $item->is_received,
                                        'condition' => $condition,
                                        'condition_raw' => $item->condition,
                                        'received_at' => $item->received_at ? $item->received_at->format('d/m/Y H:i') : null,
                                    ];
                                })
                                ->filter()
                                ->values()
                                ->toArray();
                        }

                        $productLines = ProductLine::where('is_active', true)
                            ->orderBy('name')
                            ->get(['id', 'name', 'code'])
                            ->map(fn ($pl) => [
                                'id' => (int) $pl->id,
                                'name' => (string) $pl->name,
                                'code' => (string) $pl->code,
                            ])
                            ->toArray();

                        $statuses = collect(AssetStatus::cases())->map(fn (AssetStatus $s) => [
                            'value' => $s->value,
                            'label' => $s->getLabel(),
                        ])->toArray();

                        return [
                            'isEdit' => $isEdit,
                            'initialAssets' => $initialAssets,
                            'productLines' => $productLines,
                            'statuses' => $statuses,
                            'warehouseId' => null,
                            'apiUrl' => route('filament.checkin-assets'),
                            'batchId' => $record?->id,
                            'batchCode' => $record?->code,
                            'batchStatus' => $record?->status instanceof BatchStatus ? $record->status->value : ($record?->status ?? 'pending'),
                            'receiveUrl' => route('filament.checkin-receive-item'),
                            'completeUrl' => route('filament.checkin-complete-batch'),
                        ];
                    })
                    ->default([])
                    ->afterStateHydrated(function ($component, $state, ?CheckinBatch $record) {
                        if ($record) {
                            $component->state($record->items()->pluck('asset_id')->map(fn ($id) => (int) $id)->toArray());
                        }
                    })
                    ->dehydrated(true)
                    ->columnSpanFull(),
            ]);
    }
}
