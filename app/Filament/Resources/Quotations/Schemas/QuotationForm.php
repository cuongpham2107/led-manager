<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Enums\AssetStatus;
use App\Enums\QuotationStatus;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Asset;
use App\Models\Customer;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\Warehouse;
use App\Services\AvailabilityService;
use App\Services\LedCalculationService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 12])
                    ->columnSpanFull()
                    ->schema([

                        // ================= LEFT COLUMN: Customer & Job Parameters (Span 4) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 4])
                            ->schema([

                                Section::make('Khách hàng & Sự kiện')
                                    ->description('Thông tin đối tác và thời gian sự kiện')
                                    ->schema([
                                        TextInput::make('code')
                                            ->label('Mã báo giá')
                                            ->default(function () {
                                                $count = Quotation::count() + 1;
                                                $code = 'QUO-'.date('ym').'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
                                                while (Quotation::where('code', $code)->exists()) {
                                                    $count++;
                                                    $code = 'QUO-'.date('ym').'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
                                                }

                                                return $code;
                                            })
                                            ->required(),
                                        Select::make('customer_id')
                                            ->label('Khách hàng')
                                            ->relationship('customer', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->createOptionForm(fn (Schema $schema) => CustomerForm::configure($schema))
                                            ->createOptionModalHeading('Thêm khách hàng mới')
                                            ->live()
                                            ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateBomAndPricing($get, $set, $record)),
                                        TextInput::make('event_name')
                                            ->label('Tên sự kiện')
                                            ->placeholder('VD: Lễ Ra Mắt Xe Điện VinFast VF3'),
                                        TextInput::make('location')
                                            ->label('Địa điểm tổ chức')
                                            ->placeholder('VD: Trung tâm Hội nghị Quốc gia NCC, Hà Nội'),
                                        Grid::make(2)->schema([
                                            DatePicker::make('event_start_date')
                                                ->label('Ngày bắt đầu')
                                                ->default(fn () => now()->addDays(3)->toDateString())
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(function (Get $get, Set $set, ?Quotation $record) {
                                                    self::updateRentalDaysFromDates($get, $set);
                                                    self::recalculateBomAndPricing($get, $set, $record);
                                                }),
                                            DatePicker::make('event_end_date')
                                                ->label('Ngày kết thúc')
                                                ->default(fn () => now()->addDays(6)->toDateString())
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(function (Get $get, Set $set, ?Quotation $record) {
                                                    self::updateRentalDaysFromDates($get, $set);
                                                    self::recalculateBomAndPricing($get, $set, $record);
                                                }),
                                        ]),
                                        Grid::make(2)->schema([
                                            Select::make('status')
                                                ->label('Trạng thái')
                                                ->options(QuotationStatus::class)
                                                ->default(QuotationStatus::Draft)
                                                ->required(),
                                            Select::make('sales_user_id')
                                                ->label('Sales phụ trách')
                                                ->relationship('salesUser', 'name')
                                                ->default(fn () => Auth::id())
                                                ->searchable()
                                                ->preload(),
                                        ]),
                                    ]),

                                Section::make('Thông số màn hình & Dự án (Job Parameters)')
                                    ->description('Kích thước màn hình, dòng bóng LED và số ngày thuê')
                                    ->schema([
                                        Select::make('product_line_id')
                                            ->label('Dòng Module LED (Pixel pitch)')
                                            ->relationship('productLine', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->default(fn () => ProductLine::where('code', 'P2.6')->value('id') ?? ProductLine::value('id'))
                                            ->live()
                                            ->helperText(function (Get $get) {
                                                $plId = $get('product_line_id');
                                                $rentalDays = (int) ($get('rental_days') ?: 1);
                                                $customerId = $get('customer_id');
                                                $customer = $customerId ? Customer::find($customerId) : null;
                                                $productLine = $plId ? ProductLine::find($plId) : null;
                                                if (! $productLine) {
                                                    return null;
                                                }

                                                $rates = app(LedCalculationService::class)->resolvePricing($productLine, $rentalDays, $customer?->type?->value);
                                                $baseFmt = number_format($rates['base_price'], 0, ',', '.');
                                                $discText = $rates['discount_percent'] > 0 ? " (Đã giảm {$rates['discount_percent']}% theo bảng giá)" : '';

                                                return "⚡ Giá áp dụng: {$baseFmt} đ/cabinet/ngày{$discText}";
                                            })
                                            ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateBomAndPricing($get, $set, $record)),
                                        Grid::make(2)->schema([
                                            TextInput::make('screen_width_m')
                                                ->label('Chiều rộng (m)')
                                                ->numeric()
                                                ->default(6.0)
                                                ->live(debounce: 300)
                                                ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateBomAndPricing($get, $set, $record)),
                                            TextInput::make('screen_height_m')
                                                ->label('Chiều cao (m)')
                                                ->numeric()
                                                ->default(3.5)
                                                ->live(debounce: 300)
                                                ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateBomAndPricing($get, $set, $record)),
                                        ]),
                                        Grid::make(3)->schema([
                                            TextInput::make('rental_days')
                                                ->label('Số ngày thuê')
                                                ->numeric()
                                                ->default(3)
                                                ->suffix('ngày')
                                                ->live(debounce: 300)
                                                ->helperText(function (Get $get) {
                                                    $plId = $get('product_line_id');
                                                    $rentalDays = (int) ($get('rental_days') ?: 1);
                                                    $customerId = $get('customer_id');
                                                    $customer = $customerId ? Customer::find($customerId) : null;
                                                    $productLine = $plId ? ProductLine::find($plId) : null;
                                                    if (! $productLine) {
                                                        return null;
                                                    }

                                                    $rates = app(LedCalculationService::class)->resolvePricing($productLine, $rentalDays, $customer?->type?->value);
                                                    if ($rates['discount_percent'] > 0) {
                                                        return "Ưu đãi bảng giá: Giảm {$rates['discount_percent']}% cho thuê {$rentalDays} ngày";
                                                    }

                                                    return 'Áp dụng bảng giá theo ngày tiêu chuẩn';
                                                })
                                                ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateBomAndPricing($get, $set, $record)),
                                            TextInput::make('crew_size')
                                                ->label('Số thợ kỹ thuật')
                                                ->numeric()
                                                ->default(4)
                                                ->suffix('người')
                                                ->live(debounce: 300)
                                                ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateBomAndPricing($get, $set, $record)),
                                            TextInput::make('transport_distance_km')
                                                ->label('Khoảng cách')
                                                ->numeric()
                                                ->default(45)
                                                ->suffix('km')
                                                ->live(debounce: 300)
                                                ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateBomAndPricing($get, $set, $record)),
                                        ]),
                                    ]),
                            ]),

                        // ================= RIGHT COLUMN: Derived Config, BOM & Pricing (Span 8) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 8])
                            ->schema([

                                Section::make('Cấu hình tính toán tự động (Derived Configuration)')
                                    ->description('Các chỉ số kỹ thuật quy đổi tự động')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextInput::make('screen_area_m2')
                                                ->label('Diện tích (Area)')
                                                ->numeric()
                                                ->default(21.0)
                                                ->disabled()
                                                ->dehydrated()
                                                ->suffix('m²'),
                                            TextInput::make('estimated_cabinet_qty')
                                                ->label('Số Cabinets')
                                                ->numeric()
                                                ->default(84)
                                                ->disabled()
                                                ->dehydrated()
                                                ->suffix('tấm'),
                                            TextInput::make('estimated_load_kg')
                                                ->label('Tổng tải trọng')
                                                ->numeric()
                                                ->default(631.2)
                                                ->disabled()
                                                ->dehydrated()
                                                ->suffix('kg'),
                                            TextInput::make('estimated_power_kw')
                                                ->label('Công suất điện')
                                                ->numeric()
                                                ->default(31.9)
                                                ->disabled()
                                                ->dehydrated()
                                                ->suffix('kW'),
                                        ]),
                                    ]),

                                Section::make('Danh mục thiết bị & vật tư cần có (Bill of Materials - BOM)')
                                    ->description('Danh sách thiết bị kho cần chuẩn bị xuất kho cho đơn hàng thuê')
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship('items')
                                            ->label('Danh sách thiết bị & vật tư (BOM)')
                                            ->live()
                                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateFromRepeater($get, $set))
                                            ->default(function () {
                                                $defaultPl = ProductLine::where('code', 'P2.6')->first() ?? ProductLine::first();
                                                $service = app(LedCalculationService::class);
                                                $bom = $service->generateBom(6.0, 3.5, $defaultPl, 3);
                                                $items = [];
                                                foreach ($bom as $item) {
                                                    $items[] = [
                                                        'product_line_id' => $item['product_line_id'],
                                                        'description' => $item['item'],
                                                        'quantity' => $item['qty'],
                                                        'unit_cost' => $item['unit_cost'],
                                                        'line_total' => $item['line_total'],
                                                    ];
                                                }

                                                return $items;
                                            })
                                            ->table([
                                                TableColumn::make('Thiết bị / Vật tư kho')
                                                    ->width('34%')
                                                    ->markAsRequired(),
                                                TableColumn::make('SL Cần Xuất')
                                                    ->width('6%')
                                                    ->markAsRequired()
                                                    ->alignment(Alignment::Center),
                                                TableColumn::make('Đơn giá đợt thuê')
                                                    ->width('15%')
                                                    ->alignment(Alignment::Center),
                                                TableColumn::make('Thành tiền')
                                                    ->width('15%')
                                                    ->alignment(Alignment::Center),
                                                TableColumn::make('Ghi chú quy cách')
                                                    ->width('30%'),
                                            ])
                                            ->schema([
                                                Select::make('product_line_id')
                                                    ->label('Dòng SP LED')
                                                    ->relationship('productLine', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->helperText(function ($state, Get $get, ?Model $record) {
                                                        if (! $state) {
                                                            return null;
                                                        }
                                                        $startDate = $get('../../event_start_date');
                                                        $endDate = $get('../../event_end_date') ?: $startDate;
                                                        if (! $startDate) {
                                                            return null;
                                                        }
                                                        $whId = auth()->user()?->getScopedWarehouseId();

                                                        $readyCount = Asset::query()
                                                            ->where('product_line_id', (int) $state)
                                                            ->where('current_status', AssetStatus::Ready)
                                                            ->when($whId, fn ($q) => $q->where('current_warehouse_id', $whId))
                                                            ->count();

                                                        $convertedOrderId = $get('../../converted_order_id')
                                                            ?: ($record instanceof Quotation ? $record->converted_order_id : ($record?->quotation?->converted_order_id ?? null));

                                                        $avail = app(AvailabilityService::class)->getAvailableCount(
                                                            (int) $state,
                                                            $startDate,
                                                            $endDate,
                                                            $whId ? (int) $whId : null,
                                                            $convertedOrderId ? (int) $convertedOrderId : null,
                                                        );

                                                        $startFmt = Carbon::parse($startDate)->format('d/m');
                                                        $endFmt = Carbon::parse($endDate)->format('d/m');
                                                        $dateLabel = $startFmt === $endFmt ? $startFmt : "{$startFmt}-{$endFmt}";

                                                        $breakdownStr = '';
                                                        if (! $whId) {
                                                            $warehouses = Warehouse::query()->where('is_active', true)->get();
                                                            $breakdown = [];
                                                            foreach ($warehouses as $w) {
                                                                $wAvail = app(AvailabilityService::class)->getAvailableCount(
                                                                    (int) $state,
                                                                    $startDate,
                                                                    $endDate,
                                                                    $w->id,
                                                                    $convertedOrderId ? (int) $convertedOrderId : null,
                                                                );
                                                                $code = str_replace(['WH-', 'Kho '], '', $w->code ?: $w->name);
                                                                $breakdown[] = "{$code}: {$wAvail}";
                                                            }
                                                            if (! empty($breakdown)) {
                                                                $breakdownStr = ' ['.implode(', ', $breakdown).']';
                                                            }
                                                        }

                                                        if ($avail > 0) {
                                                            return "Tồn sẵn sàng: {$readyCount} | Khả dụng lịch ({$dateLabel}): {$avail} thiết bị{$breakdownStr}";
                                                        }

                                                        return "Tồn sẵn sàng: {$readyCount} | Khả dụng lịch ({$dateLabel}): 0 thiết bị (Đã kín lịch thuê){$breakdownStr}";
                                                    })
                                                    ->afterStateUpdated(function ($state, Get $get, Set $set, Component $component) {
                                                        if (! $state) {
                                                            return;
                                                        }

                                                        $productLine = ProductLine::find($state);
                                                        if (! $productLine) {
                                                            return;
                                                        }

                                                        $wMm = (int) ($productLine->module_width_mm ?: 500);
                                                        $hMm = (int) ($productLine->module_height_mm ?: 500);

                                                        $rentalDays = (int) ($get('../../rental_days') ?: 1);
                                                        $customerId = $get('../../customer_id');
                                                        $customer = $customerId ? Customer::find($customerId) : null;
                                                        $customerType = $customer?->type?->value;

                                                        $rates = app(LedCalculationService::class)->resolvePricing($productLine, $rentalDays, $customerType);
                                                        $dayRate = (float) ($rates['base_price'] ?? 0);
                                                        $dayRateFmt = number_format($dayRate, 0, ',', '.');
                                                        $descNote = $rentalDays > 1 ? " ({$dayRateFmt} đ/ngày × {$rentalDays} ngày)" : ($dayRate > 0 ? " ({$dayRateFmt} đ/ngày)" : '');

                                                        $set('description', "Cabinet LED {$productLine->name} ({$wMm}×{$hMm}mm){$descNote}");

                                                        $currentCost = (float) str_replace(',', '', (string) ($get('unit_cost') ?: 0));
                                                        if ($currentCost <= 0 && $dayRate > 0) {
                                                            $unitCost = $dayRate * $rentalDays;
                                                            $set('unit_cost', $unitCost);
                                                            self::recalculateLineTotal($get, $set, $component);
                                                        }
                                                    }),
                                                TextInput::make('quantity')
                                                    ->label('Số lượng')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(1)
                                                    ->extraInputAttributes(['class' => 'text-center'])
                                                    ->live(debounce: 300)
                                                    ->afterStateUpdated(fn (Get $get, Set $set, Component $component) => self::recalculateLineTotal($get, $set, $component)),
                                                TextInput::make('unit_cost')
                                                    ->label('Đơn giá')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->extraInputAttributes(['class' => 'text-center font-mono'])
                                                    ->live(debounce: 300)
                                                    ->afterStateUpdated(fn (Get $get, Set $set, Component $component) => self::recalculateLineTotal($get, $set, $component)),
                                                TextInput::make('line_total')
                                                    ->label('Thành tiền')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->extraInputAttributes(['class' => 'text-center font-mono font-semibold text-primary-600']),
                                                TextInput::make('description')
                                                    ->label('Ghi chú quy cách')
                                                    ->placeholder('Ghi chú thêm nếu có...'),
                                            ])
                                            ->addActionLabel('+ Thêm thiết bị / phụ kiện vào đơn')
                                            ->collapsible(false),
                                    ]),

                                Section::make('Dự toán chi phí & Tổng tiền báo giá (Cost Breakdown)')
                                    ->description('Bảng tính chi tiết giá thuê thiết bị, nhân công, vận chuyển và chiết khấu')
                                    ->schema([
                                        Grid::make(12)->schema([
                                            // Left Column (Span 7): Các khoản mục chi phí thành phần
                                            Group::make()
                                                ->columnSpan(['default' => 12, 'lg' => 7])
                                                ->schema([
                                                    TextInput::make('equipment_cost')
                                                        ->label('1. Tiền thuê thiết bị (BOM)')
                                                        ->mask(RawJs::make('$money($input)'))
                                                        ->stripCharacters(',')
                                                        ->numeric()
                                                        ->disabled()
                                                        ->dehydrated()
                                                        ->suffix(' đ')
                                                        ->helperText('Tự động tính từ tổng giá trị cabinet & linh kiện danh mục BOM ở trên'),

                                                    Fieldset::make('2. Chi phí Nhân công kỹ thuật')
                                                        ->schema([
                                                            TextInput::make('crew_rate')
                                                                ->label('Đơn giá nhân công')
                                                                ->mask(RawJs::make('$money($input)'))
                                                                ->stripCharacters(',')
                                                                ->numeric()
                                                                ->default(1600000)
                                                                ->suffix(' đ/người/ngày')
                                                                ->live(debounce: 300)
                                                                ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateLabourFromRate($get, $set, $record)),

                                                            TextInput::make('labour_cost')
                                                                ->label('Tổng tiền nhân công')
                                                                ->mask(RawJs::make('$money($input)'))
                                                                ->stripCharacters(',')
                                                                ->numeric()
                                                                ->disabled()
                                                                ->dehydrated()
                                                                ->suffix(' đ')
                                                                ->helperText(function (Get $get) {
                                                                    $crew = (int) ($get('crew_size') ?: 4);
                                                                    $days = (int) ($get('rental_days') ?: 1);
                                                                    $rate = (float) str_replace(',', '', (string) ($get('crew_rate') ?: 1600000));

                                                                    return "{$crew} thợ × ".number_format($rate, 0, ',', '.')." đ × {$days} ngày";
                                                                }),
                                                        ])
                                                        ->columns(2),

                                                    Fieldset::make('3. Chi phí Vận chuyển & Bốc xếp')
                                                        ->schema([
                                                            TextInput::make('transport_rate')
                                                                ->label('Đơn giá cước km')
                                                                ->mask(RawJs::make('$money($input)'))
                                                                ->stripCharacters(',')
                                                                ->numeric()
                                                                ->default(28000)
                                                                ->suffix(' đ/km')
                                                                ->live(debounce: 300)
                                                                ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateTransportFromRate($get, $set, $record)),

                                                            TextInput::make('transport_cost')
                                                                ->label('Tổng tiền vận chuyển')
                                                                ->mask(RawJs::make('$money($input)'))
                                                                ->stripCharacters(',')
                                                                ->numeric()
                                                                ->disabled()
                                                                ->dehydrated()
                                                                ->suffix(' đ')
                                                                ->helperText(function (Get $get) {
                                                                    $dist = (float) ($get('transport_distance_km') ?: 45);
                                                                    $rate = (float) str_replace(',', '', (string) ($get('transport_rate') ?: 28000));

                                                                    return "{$dist} km × 2 chiều × ".number_format($rate, 0, ',', '.').' đ/km';
                                                                }),
                                                        ])
                                                        ->columns(2),

                                                    TextInput::make('accessory_cost')
                                                        ->label('4. Vật tư phụ & tiêu hao')
                                                        ->mask(RawJs::make('$money($input)'))
                                                        ->stripCharacters(',')
                                                        ->numeric()
                                                        ->suffix(' đ')
                                                        ->helperText('Dây tín hiệu, nguồn, phụ kiện lắp ráp dự phòng')
                                                        ->live(debounce: 300)
                                                        ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateTotals($get, $set, $record)),
                                                ]),

                                            // Right Column (Span 5): Bảng tổng kết thanh toán (Summary Card)
                                            Group::make()
                                                ->columnSpan(['default' => 12, 'lg' => 5])
                                                ->schema([
                                                    Section::make('Tổng kết báo giá')
                                                        ->schema([
                                                            TextInput::make('discount_amount')
                                                                ->label('Chiết khấu / Giảm giá')
                                                                ->mask(RawJs::make('$money($input)'))
                                                                ->stripCharacters(',')
                                                                ->numeric()
                                                                ->default(0)
                                                                ->suffix(' đ')
                                                                ->live(debounce: 300)
                                                                ->afterStateUpdated(fn (Get $get, Set $set, ?Quotation $record) => self::recalculateTotals($get, $set, $record)),

                                                            TextInput::make('total_price')
                                                                ->label('TỔNG CỘNG THANH TOÁN')
                                                                ->mask(RawJs::make('$money($input)'))
                                                                ->stripCharacters(',')
                                                                ->numeric()
                                                                ->disabled()
                                                                ->dehydrated()
                                                                ->suffix(' đ')
                                                                ->extraInputAttributes(['class' => 'font-extrabold text-2xl text-primary-600 font-mono tracking-tight']),

                                                            TextInput::make('margin_percent')
                                                                ->label('Biên lợi nhuận dự kiến (Margin)')
                                                                ->numeric()
                                                                ->disabled()
                                                                ->dehydrated()
                                                                ->suffix('%')
                                                                ->helperText('Tỷ suất lợi nhuận gộp trên doanh thu'),

                                                            Textarea::make('note')
                                                                ->label('Ghi chú điều khoản báo giá')
                                                                ->rows(3)
                                                                ->placeholder('Ghi chú thêm về điều khoản thanh toán, bảo hành...'),
                                                        ]),
                                                ]),
                                        ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function recalculateLineTotal(Get $get, Set $set, ?Component $component = null): void
    {
        $qty = (float) str_replace(',', '', (string) ($get('quantity') ?: 0));
        $cost = (float) str_replace(',', '', (string) ($get('unit_cost') ?: 0));
        $lineTotal = $qty * $cost;
        $set('line_total', $lineTotal);

        $currentRowKey = null;
        if ($component) {
            $statePath = $component->getStatePath();
            if (str_contains($statePath, 'items.')) {
                $currentRowKey = (string) str($statePath)->after('items.')->before('.');
            }
        }

        $items = $get('../../items');
        $totalEquipment = 0;
        if (is_array($items)) {
            foreach ($items as $key => $item) {
                if ($currentRowKey !== null && (string) $key === (string) $currentRowKey) {
                    $totalEquipment += $lineTotal;
                } else {
                    $itemQty = (float) str_replace(',', '', (string) ($item['quantity'] ?? 0));
                    $itemCost = (float) str_replace(',', '', (string) ($item['unit_cost'] ?? 0));
                    $totalEquipment += ($itemQty * $itemCost);
                }
            }
        } else {
            $totalEquipment = $lineTotal;
        }

        $set('../../equipment_cost', $totalEquipment);

        $labour = (float) str_replace(',', '', (string) ($get('../../labour_cost') ?: 0));
        $trans = (float) str_replace(',', '', (string) ($get('../../transport_cost') ?: 0));
        $acc = (float) str_replace(',', '', (string) ($get('../../accessory_cost') ?: 0));
        $discount = (float) str_replace(',', '', (string) ($get('../../discount_amount') ?: 0));

        $totalCost = ($totalEquipment * 0.4) + ($labour * 0.7) + ($trans * 0.6) + $acc;
        $totalPrice = max(0, ($totalEquipment + $labour + $trans + $acc) - $discount);
        $margin = $totalPrice > 0 ? round((($totalPrice - $totalCost) / $totalPrice) * 100, 1) : 0.0;

        $set('../../total_cost', $totalCost);
        $set('../../total_price', $totalPrice);
        $set('../../margin_percent', $margin);
    }

    public static function recalculateFromRepeater(Get $get, Set $set): void
    {
        $items = $get('items');
        $totalEquipment = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $itemQty = (float) str_replace(',', '', (string) ($item['quantity'] ?? 0));
                $itemCost = (float) str_replace(',', '', (string) ($item['unit_cost'] ?? 0));
                $totalEquipment += ($itemQty * $itemCost);
            }
        }

        $set('equipment_cost', $totalEquipment);
        self::recalculateTotals($get, $set);
    }

    public static function recalculateLabourFromRate(Get $get, Set $set, ?Quotation $record = null): void
    {
        $crewSize = (float) ($get('crew_size') ?: 0);
        $rentalDays = (float) ($get('rental_days') ?: 1);
        $crewRate = (float) str_replace(',', '', (string) ($get('crew_rate') ?: 0));
        $labourCost = $crewSize * $crewRate * $rentalDays;
        $set('labour_cost', $labourCost);
        self::recalculateTotals($get, $set, $record);
    }

    public static function recalculateTransportFromRate(Get $get, Set $set, ?Quotation $record = null): void
    {
        $dist = (float) ($get('transport_distance_km') ?: 0);
        $transRate = (float) str_replace(',', '', (string) ($get('transport_rate') ?: 0));
        $transportCost = ($dist * 2) * $transRate;
        $set('transport_cost', $transportCost);
        self::recalculateTotals($get, $set, $record);
    }

    public static function recalculateBomAndPricing(Get $get, Set $set, ?Quotation $record = null): void
    {
        $width = (float) ($get('screen_width_m') ?: 6.0);
        $height = (float) ($get('screen_height_m') ?: 3.5);
        $plId = $get('product_line_id');
        $productLine = $plId ? ProductLine::find($plId) : null;
        $rentalDays = (int) ($get('rental_days') ?: 3);
        $crewSize = (int) ($get('crew_size') ?: 4);
        $transportDistance = (float) ($get('transport_distance_km') ?: 45.0);
        $discount = (float) str_replace(',', '', (string) ($get('discount_amount') ?: 0.0));

        $customerId = $get('customer_id');
        $customer = $customerId ? Customer::find($customerId) : null;
        $customerType = $customer?->type?->value;

        $service = app(LedCalculationService::class);
        $config = $service->deriveConfiguration($width, $height, $productLine);

        // ponytail: derived config always recomputes (cheap, deterministic)
        $set('screen_area_m2', $config['wall_area']);
        $set('estimated_cabinet_qty', $config['cabinets_qty']);
        $set('estimated_processor_qty', 2);
        $set('estimated_load_kg', $config['load_kg']);
        $set('estimated_power_kw', $config['peak_power_kw']);

        // ponytail: when editing, keep saved BOM/cost/total_price intact;
        // user must explicitly press "Recalculate" (TODO) to refresh from screen params.
        if ($record !== null) {
            return;
        }

        $bom = $service->generateBom($width, $height, $productLine, $rentalDays, $customerType);
        $rates = $service->resolvePricing($productLine, $rentalDays, $customerType);

        // Set items repeater with clean fields
        $items = [];
        $equipmentRental = 0;
        foreach ($bom as $item) {
            $items[] = [
                'product_line_id' => $item['product_line_id'],
                'description' => $item['item'],
                'quantity' => $item['qty'],
                'unit_cost' => $item['unit_cost'],
                'line_total' => $item['line_total'],
            ];
            $equipmentRental += $item['line_total'];
        }
        $set('items', $items);

        // Resolve rates: if user has custom rate, keep it, otherwise take from pricing rule
        $crewRate = $get('crew_rate') !== null && $get('crew_rate') !== ''
            ? (float) str_replace(',', '', (string) $get('crew_rate'))
            : $rates['crew_rate'];
        $transRate = $get('transport_rate') !== null && $get('transport_rate') !== ''
            ? (float) str_replace(',', '', (string) $get('transport_rate'))
            : $rates['transport_rate'];

        $set('crew_rate', $crewRate);
        $set('transport_rate', $transRate);

        $crewLabour = $crewSize * $crewRate * $rentalDays;
        $transport = ($transportDistance * 2) * $transRate;
        $accessory = $config['wall_area'] * $rates['accessory_rate'];

        $totalCost = ($equipmentRental * 0.40) + ($crewLabour * 0.70) + ($transport * 0.60) + $accessory;
        $rawPrice = $equipmentRental + $crewLabour + $transport + $accessory;
        $totalPrice = max(0, $rawPrice - $discount);
        $marginPercent = $totalPrice > 0 ? round((($totalPrice - $totalCost) / $totalPrice) * 100, 1) : 0.0;

        $set('equipment_cost', $equipmentRental);
        $set('labour_cost', $crewLabour);
        $set('transport_cost', $transport);
        $set('accessory_cost', $accessory);
        $set('total_cost', $totalCost);
        $set('total_price', $totalPrice);
        $set('margin_percent', $marginPercent);
    }

    public static function recalculateTotals(Get $get, Set $set, ?Quotation $record = null): void
    {
        $eq = (float) str_replace(',', '', (string) ($get('equipment_cost') ?: 0));
        $labour = (float) str_replace(',', '', (string) ($get('labour_cost') ?: 0));
        $trans = (float) str_replace(',', '', (string) ($get('transport_cost') ?: 0));
        $acc = (float) str_replace(',', '', (string) ($get('accessory_cost') ?: 0));
        $discount = (float) str_replace(',', '', (string) ($get('discount_amount') ?: 0));

        $totalCost = ($eq * 0.4) + ($labour * 0.7) + ($trans * 0.6) + $acc;
        $totalPrice = max(0, ($eq + $labour + $trans + $acc) - $discount);
        $margin = $totalPrice > 0 ? round((($totalPrice - $totalCost) / $totalPrice) * 100, 1) : 0.0;

        $set('total_cost', $totalCost);
        $set('total_price', $totalPrice);
        $set('margin_percent', $margin);
    }

    public static function updateRentalDaysFromDates(Get $get, Set $set): void
    {
        $start = $get('event_start_date');
        $end = $get('event_end_date');
        if ($start && $end) {
            $days = (int) (Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1);
            if ($days > 0) {
                $set('rental_days', $days);
            }
        }
    }
}
