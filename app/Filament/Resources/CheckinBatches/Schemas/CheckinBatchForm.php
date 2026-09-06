<?php

namespace App\Filament\Resources\CheckinBatches\Schemas;

use App\Enums\AssetStatus;
use App\Models\CheckinBatch;
use App\Services\CodeGeneratorService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
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
                            ->required(),

                        DatePicker::make('expected_date')
                            ->label('Ngày dự kiến')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->placeholder('DD/MM/YYYY')
                            ->extraAttributes(['class' => 'relative z-30']),
                    ])
                    ->extraAttributes(['class' => 'relative z-30'])
                    ->columnSpanFull(),

                ViewField::make('selected_assets')
                    ->label('Mã hàng trong đợt')
                    ->view('filament.components.checkin-batch-assets-selector')
                    ->viewData(function ($record = null, string $operation = 'create') {
                        $isEdit = ($record instanceof CheckinBatch) || $operation === 'edit';
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

                                    return [
                                        'id' => (int) $asset->id,
                                        'serial_no' => (string) $asset->serial_no,
                                        'name' => (string) ($asset->productLine?->name ?? 'LED'),
                                        'size' => (string) ($asset->size ?? '0.5×0.5 m'),
                                        'status' => (string) $statusLabel,
                                        'status_color' => (string) $statusColor,
                                    ];
                                })
                                ->filter()
                                ->values()
                                ->toArray();
                        }

                        return [
                            'isEdit' => $isEdit,
                            'initialAssets' => $initialAssets,
                            'apiUrl' => route('filament.checkin-assets'),
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
