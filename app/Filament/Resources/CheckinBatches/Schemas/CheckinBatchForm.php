<?php

namespace App\Filament\Resources\CheckinBatches\Schemas;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Models\Agency;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CodeGeneratorService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

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

                Grid::make(['default' => 1, 'md' => 3])
                    ->schema([
                        Select::make('agency_id')
                            ->label('Đại lý tiếp nhận (Nếu có)')
                            ->placeholder('— Nhập về Kho Tổng (HQ) —')
                            ->options(fn () => Agency::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->default(function (?CheckinBatch $record) {
                                if ($record) {
                                    return $record->agency_id ?? $record->warehouse?->agency?->id;
                                }
                                $user = Auth::user();

                                return $user instanceof User ? $user->getScopedAgencyId() : null;
                            })
                            ->disabled(function () {
                                $user = Auth::user();

                                return (bool) ($user instanceof User && $user->getScopedAgencyId());
                            })
                            ->dehydrated()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $agency = Agency::find($state);
                                    if ($agency && $agency->warehouse_id) {
                                        $set('warehouse_id', $agency->warehouse_id);
                                    }
                                } else {
                                    $defaultHq = Warehouse::whereDoesntHave('agency')->where('is_active', true)->first();
                                    $set('warehouse_id', $defaultHq?->id);
                                }
                            }),

                        Select::make('warehouse_id')
                            ->label('Kho lưu trữ tiếp nhận')
                            ->options(function (Get $get) {
                                $agencyId = $get('agency_id');
                                if ($agencyId) {
                                    $agency = Agency::find($agencyId);

                                    return $agency && $agency->warehouse
                                        ? [$agency->warehouse_id => "{$agency->warehouse->name} [Đại lý: {$agency->name}]"]
                                        : [];
                                }

                                return Warehouse::whereDoesntHave('agency')
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->default(function (?CheckinBatch $record) {
                                if ($record) {
                                    return $record->warehouse_id;
                                }
                                $user = Auth::user();
                                if ($user instanceof User && $user->getScopedWarehouseId()) {
                                    return $user->getScopedWarehouseId();
                                }

                                return Warehouse::whereDoesntHave('agency')->where('is_active', true)->first()?->id;
                            })
                            ->disabled(function (Get $get) {
                                $user = Auth::user();
                                if ($user instanceof User && $user->getScopedWarehouseId()) {
                                    return true;
                                }

                                return (bool) $get('agency_id');
                            })
                            ->dehydrated()
                            ->required()
                            ->live()
                            ->helperText(function (Get $get) {
                                $whId = $get('warehouse_id');
                                if (! $whId) {
                                    return 'Chọn kho tiếp nhận để kiểm tra đơn vị quản lý và hạn mức khả dụng.';
                                }
                                $agency = Agency::where('warehouse_id', $whId)->first();
                                if (! $agency) {
                                    return '🏢 Kho Tổng công ty (HQ) — Không áp dụng giới hạn định mức đại lý.';
                                }
                                $allocated = (float) $agency->allocated_area_m2;
                                $current = $agency->current_inventory_area;
                                $remaining = max(0, round($allocated - $current, 2));
                                $percent = $allocated > 0 ? round(($current / $allocated) * 100, 1) : 0;
                                $contact = $agency->contact_person ? " | LH: {$agency->contact_person} ({$agency->phone})" : '';

                                return "🏢 Phân phối cho: {$agency->name} ({$agency->code}){$contact} | Định mức: {$allocated} m² | Đang chứa: {$current} m² ({$percent}%) | Còn trống: {$remaining} m²";
                            }),

                        DatePicker::make('expected_date')
                            ->label('Ngày dự kiến')
                            ->displayFormat('d/m/Y')
                            ->native(true)
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

                        if ($isEdit && $record instanceof CheckinBatch) {
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

                                    $isReceived = (bool) $item->is_received;
                                    $condition = null;
                                    if ($isReceived) {
                                        if ($item->condition === 'fault') {
                                            $condition = 'damaged';
                                        } elseif ($item->condition === 'ok') {
                                            $condition = 'normal';
                                        }
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
                                        'is_received' => $isReceived,
                                        'condition' => $condition,
                                        'condition_raw' => $isReceived ? $item->condition : null,
                                        'received_at' => ($isReceived && $item->received_at) ? $item->received_at->format('d/m/Y H:i') : null,
                                    ];
                                })
                                ->filter()
                                ->values()
                                ->toArray();
                        } elseif (! $isEdit) {
                            $initialAssets = Asset::where(function ($q) {
                                $q->where('current_status', AssetStatus::NewlyAdded)
                                    ->orWhereNull('current_warehouse_id');
                            })
                                ->with('productLine')
                                ->get()
                                ->map(function ($asset) {
                                    $statusLabel = $asset->current_status instanceof AssetStatus
                                        ? $asset->current_status->getLabel()
                                        : 'Mới';
                                    $statusColor = $asset->current_status instanceof AssetStatus
                                        ? $asset->current_status->getColor()
                                        : 'primary';

                                    return [
                                        'id' => (int) $asset->id,
                                        'item_id' => null,
                                        'serial_no' => (string) $asset->serial_no,
                                        'product_line_id' => (int) $asset->product_line_id,
                                        'name' => (string) ($asset->productLine?->name ?? 'LED'),
                                        'size' => (string) ($asset->size ?? '0.5×0.5 m'),
                                        'status' => (string) $statusLabel,
                                        'status_raw' => (string) ($asset->current_status instanceof AssetStatus ? $asset->current_status->value : $asset->current_status),
                                        'status_color' => (string) $statusColor,
                                        'is_received' => false,
                                        'condition' => null,
                                        'condition_raw' => null,
                                        'received_at' => null,
                                    ];
                                })
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

                        return [
                            'isEdit' => $isEdit,
                            'initialAssets' => $initialAssets,
                            'productLines' => $productLines,
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
