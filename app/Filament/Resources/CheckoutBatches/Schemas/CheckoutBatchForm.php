<?php

namespace App\Filament\Resources\CheckoutBatches\Schemas;

use App\Models\CheckoutBatch;
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
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CheckoutBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Mã đợt')
                    ->default(fn () => CodeGeneratorService::generate('OUT', 'checkout_batches').' · Hệ thống tự sinh')
                    ->disabled()
                    ->dehydrated()
                    ->columnSpanFull(),

                TextInput::make('note')
                    ->label('Ghi chú')
                    ->placeholder('')
                    ->columnSpanFull(),

                Select::make('warehouse_id')
                    ->label('Kho hàng')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->default(function () {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user?->getScopedWarehouseId() ?: Warehouse::first()?->id;
                    })
                    ->required()
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        Select::make('customer_id')
                            ->label('Khách hàng')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn khách hàng...'),

                        DatePicker::make('export_date')
                            ->label('Ngày cần xuất')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now()),
                    ])
                    ->columnSpanFull(),

                DatePicker::make('expected_return_date')
                    ->label('Ngày dự kiến trả')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now())
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        TextInput::make('required_area_m2')
                            ->label('Diện tích cần xuất (m²)')
                            ->numeric()
                            ->default(0)
                            ->live(debounce: 300),

                        Select::make('purpose')
                            ->label('Mục đích sử dụng')
                            ->options([
                                'Sự kiện' => 'Sự kiện',
                                'Cho thuê' => 'Cho thuê',
                                'Triển lãm / Hội nghị' => 'Triển lãm / Hội nghị',
                                'Bảo dưỡng / Sửa chữa' => 'Bảo dưỡng / Sửa chữa',
                                'Khác' => 'Khác',
                            ])
                            ->default('Sự kiện'),
                    ])
                    ->columnSpanFull(),

                Select::make('product_line_id')
                    ->label('Loại thiết bị cần')
                    ->placeholder('Tất cả dòng sản phẩm')
                    ->options(fn () => ProductLine::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->columnSpanFull(),

                ViewField::make('selected_assets')
                    ->label('Mã seri gợi ý')
                    ->helperText('Tự động gợi ý từ tồn kho sẵn sàng phù hợp yêu cầu — có thể điều chỉnh thêm.')
                    ->view('filament.components.checkout-batch-assets-selector')
                    ->viewData(function (Get $get, ?CheckoutBatch $record = null) {
                        $warehouseId = $get('warehouse_id');
                        $productLineId = $get('product_line_id');
                        $requiredArea = (float) ($get('required_area_m2') ?? 0);
                        $isEdit = $record instanceof CheckoutBatch && $record->exists;

                        $initialAssets = [];
                        if ($isEdit) {
                            $initialAssets = $record->items()
                                ->with('asset.productLine')
                                ->get()
                                ->map(function ($item) {
                                    $asset = $item->asset;
                                    if (! $asset) {
                                        return null;
                                    }

                                    $pl = $asset->productLine;
                                    $fullName = $pl?->name ?? 'Cabin LED';
                                    $code = $pl?->code ?: '';
                                    $shortName = $code ? preg_replace('/-FIX$/', '', $code) : explode(' ', $fullName)[0];

                                    if (str_contains($fullName, 'Sự kiện')) {
                                        $type = 'Sự kiện';
                                    } elseif (str_contains($fullName, 'Trong nhà cố định')) {
                                        $type = 'Trong nhà cố định';
                                    } elseif (str_contains($fullName, 'Outdoor') || str_contains($fullName, 'Ngoài trời')) {
                                        $type = 'Ngoài trời';
                                    } else {
                                        $type = $pl?->environment?->getLabel() ?? 'Sự kiện';
                                    }

                                    $area = 0.25;
                                    if ($pl && (float) $pl->module_width_mm > 0 && (float) $pl->module_height_mm > 0) {
                                        $area = ((float) $pl->module_width_mm / 1000) * ((float) $pl->module_height_mm / 1000);
                                    } elseif (! empty($asset->size)) {
                                        if (str_contains($asset->size, '0.5×1') || str_contains($asset->size, '0.5x1')) {
                                            $area = 0.5;
                                        } elseif (str_contains($asset->size, '0.5×0.5') || str_contains($asset->size, '0.5x0.5')) {
                                            $area = 0.25;
                                        }
                                    }

                                    return [
                                        'id' => (int) $asset->id,
                                        'serial_no' => (string) $asset->serial_no,
                                        'name' => (string) $shortName,
                                        'size' => (string) ($asset->size ?? '0.5×0.5 m'),
                                        'type' => (string) $type,
                                        'status' => (string) $asset->current_status->value,
                                        'status_label' => (string) $asset->current_status->getLabel(),
                                        'status_color' => (string) $asset->current_status->getColor(),
                                        'area_m2' => (float) $area,
                                        'is_dispatched' => (bool) $item->is_dispatched,
                                        'dispatched_at' => $item->dispatched_at?->format('d/m/Y H:i'),
                                        'checkout_batch_item_id' => (int) $item->id,
                                    ];
                                })
                                ->filter()
                                ->values()
                                ->toArray();
                        }

                        return [
                            'initialAssets' => $initialAssets,
                            'isEdit' => $isEdit,
                            'batchId' => $record?->id,
                            'batchCode' => $record?->code,
                            'warehouseId' => $warehouseId,
                            'productLineId' => $productLineId,
                            'requiredArea' => $requiredArea,
                            'apiUrl' => route('filament.checkout-assets'),
                        ];
                    })
                    ->afterStateHydrated(function ($component, $state, ?CheckoutBatch $record) {
                        if ($record) {
                            $component->state($record->items()->pluck('asset_id')->map(fn ($id) => (int) $id)->toArray());
                        }
                    })
                    ->default([])
                    ->dehydrated(true)
                    ->columnSpanFull(),
            ]);
    }
}
