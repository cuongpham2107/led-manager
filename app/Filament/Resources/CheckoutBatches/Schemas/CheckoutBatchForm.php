<?php

namespace App\Filament\Resources\CheckoutBatches\Schemas;

use App\Models\Agency;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\Order;
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

                Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Select::make('agency_id')
                            ->label('Đại lý xuất hàng (Nếu có)')
                            ->placeholder('— Xuất từ Kho Tổng (HQ) —')
                            ->options(fn () => Agency::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->default(function (?CheckoutBatch $record) {
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
                                $set('order_id', null);
                            }),

                        Select::make('warehouse_id')
                            ->label('Kho hàng xuất')
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
                            ->default(function (?CheckoutBatch $record) {
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
                                    return null;
                                }
                                $agency = Agency::where('warehouse_id', $whId)->first();
                                if (! $agency) {
                                    return '🏢 Kho Tổng công ty (HQ)';
                                }

                                return "🏢 Thuộc Đại lý: {$agency->name} ({$agency->code}) | Tồn kho hiện tại: {$agency->current_inventory_area} m² / Định mức: {$agency->allocated_area_m2} m²";
                            }),
                    ])
                    ->columnSpanFull(),

                Grid::make(['default' => 1, 'md' => 3])
                    ->schema([
                        Select::make('order_id')
                            ->label('Đơn hàng liên kết (Nếu có)')
                            ->relationship('order', 'order_no', modifyQueryUsing: function ($query, Get $get) {
                                $user = Auth::user();
                                $agencyId = ($user instanceof User ? $user->getScopedAgencyId() : null) ?: $get('agency_id');
                                if ($agencyId) {
                                    $query->where('agency_id', $agencyId);
                                } elseif ($whId = $get('warehouse_id')) {
                                    $query->where('warehouse_id', $whId);
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $order = Order::find($state);
                                    if ($order) {
                                        if ($order->customer_id) {
                                            $set('customer_id', $order->customer_id);
                                        }
                                        if ($order->area_m2) {
                                            $set('required_area_m2', $order->area_m2);
                                        }
                                    }
                                }
                            }),

                        Select::make('customer_id')
                            ->label('Khách hàng')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn khách hàng...'),

                        DatePicker::make('export_date')
                            ->label('Ngày cần xuất')
                            ->displayFormat('d/m/Y')
                            ->native(true)
                            ->default(now()),
                    ])
                    ->columnSpanFull(),

                DatePicker::make('expected_return_date')
                    ->label('Ngày dự kiến trả')
                    ->displayFormat('d/m/Y')
                    ->native(true)
                    ->default(now())
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        TextInput::make('required_area_m2')
                            ->label('Diện tích cần xuất (m²)')
                            ->numeric()
                            ->default(0)
                            ->live(debounce: 300)
                            ->rules([
                                fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $requiredArea = (float) ($value ?? 0);
                                    if ($requiredArea <= 0) {
                                        return;
                                    }
                                    $selectedIds = collect($get('selected_assets') ?? [])->map(fn ($id) => (int) $id)->filter()->values();
                                    if ($selectedIds->isNotEmpty()) {
                                        $assets = Asset::with('productLine')->whereIn('id', $selectedIds)->get();
                                        $totalArea = (float) $assets->sum(fn (Asset $a) => $a->area_m2);
                                        if (round($totalArea, 2) < round($requiredArea, 2)) {
                                            $totalFormatted = number_format($totalArea, 2);
                                            $requiredFormatted = number_format($requiredArea, 2);
                                            $fail("Kho chỉ có {$totalFormatted} m² khả dụng, không đủ {$requiredFormatted} m² theo yêu cầu. Không đủ điều kiện để xuất kho!");
                                        }
                                    }
                                },
                            ]),

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
                            'batchStatus' => $record?->status?->value,
                            'warehouseId' => $warehouseId,
                            'productLineId' => $productLineId,
                            'requiredArea' => $requiredArea,
                            'apiUrl' => route('filament.checkout-assets'),
                            'dispatchApiUrl' => route('filament.checkout-dispatch-item'),
                        ];
                    })
                    ->afterStateHydrated(function ($component, $state, ?CheckoutBatch $record) {
                        if ($record) {
                            $component->state($record->items()->pluck('asset_id')->map(fn ($id) => (int) $id)->toArray());
                        }
                    })
                    ->rules([
                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $selectedIds = collect($value)->map(fn ($id) => (int) $id)->filter()->values();
                            if ($selectedIds->isEmpty()) {
                                $fail('Vui lòng chọn ít nhất một thiết bị xuất kho.');

                                return;
                            }

                            $requiredArea = (float) ($get('required_area_m2') ?? 0);
                            if ($requiredArea > 0) {
                                $assets = Asset::with('productLine')->whereIn('id', $selectedIds)->get();
                                $totalArea = (float) $assets->sum(fn (Asset $a) => $a->area_m2);

                                if (round($totalArea, 2) < round($requiredArea, 2)) {
                                    $totalFormatted = number_format($totalArea, 2);
                                    $requiredFormatted = number_format($requiredArea, 2);
                                    $fail("Kho chỉ có {$totalFormatted} m² khả dụng, không đủ {$requiredFormatted} m² theo yêu cầu. Không đủ điều kiện để xuất kho!");
                                }
                            }
                        },
                    ])
                    ->default([])
                    ->dehydrated(true)
                    ->columnSpanFull(),
            ]);
    }
}
