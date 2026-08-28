<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\AssignmentRole;
use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Enums\OrderStatus;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Order;
use App\Models\Quotation;
use App\Services\AvailabilityService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 12])
                    ->columnSpanFull()
                    ->schema([

                        // ================= LEFT COLUMN: Order Info & Event Details (5 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 5])
                            ->schema([
                                Section::make('Thông tin đơn hàng & Khách hàng')
                                    ->description('Mã đơn hàng, khách hàng, kho xuất và người phụ trách')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('order_no')
                                                ->label('Mã đơn hàng')
                                                ->default(function () {
                                                    $count = Order::count() + 1;
                                                    $code = 'ORD-'.date('ym').'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
                                                    while (Order::where('order_no', $code)->exists()) {
                                                        $count++;
                                                        $code = 'ORD-'.date('ym').'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
                                                    }

                                                    return $code;
                                                })
                                                ->required()
                                                ->columnSpan(1),
                                            Select::make('status')
                                                ->label('Trạng thái đơn')
                                                ->options(OrderStatus::class)
                                                ->default(OrderStatus::Draft)
                                                ->required()
                                                ->columnSpan(1),
                                        ]),
                                        Select::make('customer_id')
                                            ->label('Khách hàng')
                                            ->relationship('customer', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->createOptionForm(fn (Schema $schema) => CustomerForm::configure($schema))
                                            ->createOptionModalHeading('Thêm khách hàng mới'),
                                        Select::make('warehouse_id')
                                            ->label('Kho xuất hàng')
                                            ->relationship('warehouse', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Grid::make(2)->schema([
                                            Select::make('quotation_id')
                                                ->label('Từ báo giá gốc')
                                                ->relationship('quotation', 'code')
                                                ->searchable()
                                                ->preload()
                                                ->placeholder('— Đơn tạo trực tiếp —')
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    if ($state) {
                                                        $quo = Quotation::find($state);
                                                        if ($quo) {
                                                            $set('customer_id', $quo->customer_id);
                                                            $set('sales_user_id', $quo->sales_user_id);
                                                            $set('event', $quo->event_name);
                                                            $set('value', $quo->total_price);
                                                            $set('area_m2', $quo->screen_area_m2);
                                                            $set('request_date', $quo->event_start_date);
                                                            $set('expected_return_date', $quo->event_end_date);
                                                        }
                                                    }
                                                })
                                                ->columnSpan(1),
                                            Select::make('sales_user_id')
                                                ->label('Sales phụ trách')
                                                ->relationship('salesUser', 'name')
                                                ->default(fn () => Auth::id())
                                                ->searchable()
                                                ->preload()
                                                ->columnSpan(1),
                                        ]),
                                    ]),

                                Section::make('Chi tiết sự kiện & Giá trị đơn hàng')
                                    ->description('Tên sự kiện, thời gian thi công, diện tích và giá trị hợp đồng')
                                    ->collapsible()
                                    ->schema([
                                        TextInput::make('event')
                                            ->label('Tên sự kiện')
                                            ->placeholder('VD: Lễ Ra Mắt Xe Điện VinFast VF3'),
                                        Grid::make(2)->schema([
                                            TextInput::make('value')
                                                ->label('Tổng giá trị đơn hàng')
                                                ->mask(RawJs::make('$money($input)'))
                                                ->stripCharacters(',')
                                                ->numeric()
                                                ->suffix(' đ')
                                                ->default(0)
                                                ->columnSpan(1),
                                            TextInput::make('area_m2')
                                                ->label('Tổng diện tích màn hình')
                                                ->numeric()
                                                ->suffix(' m²')
                                                ->default(0)
                                                ->columnSpan(1),
                                        ]),
                                        Grid::make(2)->schema([
                                            DatePicker::make('request_date')
                                                ->label('Ngày bắt đầu sự kiện')
                                                ->native(false)
                                                ->default(now()->toDateString())
                                                ->columnSpan(1),
                                            DatePicker::make('expected_return_date')
                                                ->label('Ngày dự kiến hoàn trả')
                                                ->native(false)
                                                ->default(now()->addDays(3)->toDateString())
                                                ->columnSpan(1),
                                        ]),
                                        Textarea::make('note')
                                            ->label('Ghi chú điều phối đơn hàng')
                                            ->placeholder('Yêu cầu kỹ thuật đặc biệt, lưu ý hiện trường...')
                                            ->rows(3),
                                    ]),
                            ]),

                        // ================= RIGHT COLUMN: Repeaters (BOM, Crew, Milestones) (7 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 7])
                            ->schema([
                                Section::make('Danh mục thiết bị cần xuất kho (Bill of Materials - Order Items)')
                                    ->description('Định mức toàn bộ thiết bị cần xuất kho cho đơn hàng thuê này')
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship('items')
                                            ->label('Danh sách thiết bị định mức xuất kho')
                                            ->table([
                                                TableColumn::make('Thiết bị / Vật tư kho'),
                                                TableColumn::make('SL Cần Xuất'),
                                                TableColumn::make('Đơn giá (VND)'),
                                                TableColumn::make('Ghi chú / Quy cách'),
                                            ])
                                            ->schema([
                                                Select::make('device_type_id')
                                                    ->label('Thiết bị / Vật tư')
                                                    ->relationship('deviceType', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->required()
                                                    ->helperText(function ($state, Get $get) {
                                                        if (! $state) {
                                                            return null;
                                                        }
                                                        $whId = $get('../../warehouse_id');
                                                        $reqDate = $get('../../request_date');
                                                        $retDate = $get('../../expected_return_date') ?: $reqDate;
                                                        if (! $reqDate) {
                                                            return null;
                                                        }

                                                        $available = app(AvailabilityService::class)->getAvailableCount(
                                                            (int) $state,
                                                            $reqDate,
                                                            $retDate,
                                                            $whId ? (int) $whId : null
                                                        );

                                                        return "Tồn kho khả dụng: {$available}";
                                                    }),
                                                TextInput::make('quantity_required')
                                                    ->label('SL Cần')
                                                    ->numeric()
                                                    ->required()
                                                    ->default(1),
                                                TextInput::make('unit_price')
                                                    ->label('Đơn giá')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->numeric()
                                                    ->suffix(' đ')
                                                    ->default(0),
                                                TextInput::make('note')
                                                    ->label('Ghi chú')
                                                    ->placeholder('Ghi chú quy cách...'),
                                            ])
                                            ->addActionLabel('+ Thêm thiết bị cần xuất')
                                            ->collapsible(false),
                                    ]),

                                Section::make('Phân công Đội ngũ Kỹ thuật & Nhân sự (Event Crew)')
                                    ->description(function (Get $get, ?Order $record) {
                                        $quoId = $get('quotation_id');
                                        $quotation = $quoId ? Quotation::find($quoId) : ($record?->quotation ?? null);
                                        $assignments = $get('assignments') ?: [];
                                        $assignedCount = is_array($assignments) ? count($assignments) : 0;

                                        if ($quotation && ! empty($quotation->crew_size)) {
                                            $requiredCount = (int) $quotation->crew_size;
                                            $diff = $requiredCount - $assignedCount;

                                            if ($diff > 0) {
                                                return "⚡ Định mức từ Báo giá [{$quotation->code}]: {$requiredCount} nhân sự | Đã phân công: {$assignedCount}/{$requiredCount} (Còn thiếu {$diff} người)";
                                            } elseif ($diff === 0) {
                                                return "✅ Định mức từ Báo giá [{$quotation->code}]: {$requiredCount} nhân sự | Đã phân công đủ {$assignedCount}/{$requiredCount} người";
                                            } else {
                                                $extra = abs($diff);

                                                return "⚠️ Định mức từ Báo giá [{$quotation->code}]: {$requiredCount} nhân sự | Đã phân công: {$assignedCount}/{$requiredCount} (Vượt định mức {$extra} người)";
                                            }
                                        }

                                        return $assignedCount > 0
                                            ? "Đã phân công {$assignedCount} nhân sự phụ trách thi công, vận hành và nhân công sự kiện."
                                            : 'Gán nhân sự phụ trách thi công, vận hành, tài xế và nhân công sự kiện.';
                                    })
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('assignments')
                                            ->relationship('assignments')
                                            ->label('Danh sách nhân sự phân công')
                                            ->live()
                                            ->table([
                                                TableColumn::make('Nhân viên / Kỹ thuật'),
                                                TableColumn::make('Vai trò'),
                                                TableColumn::make('Từ ngày'),
                                                TableColumn::make('Đến ngày'),
                                                TableColumn::make('Ghi chú nhiệm vụ'),
                                            ])
                                            ->schema([
                                                Select::make('user_id')
                                                    ->label('Nhân sự')
                                                    ->relationship('user', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                Select::make('role')
                                                    ->label('Vai trò')
                                                    ->options(AssignmentRole::class)
                                                    ->default(AssignmentRole::Technician)
                                                    ->required(),
                                                DatePicker::make('start_date')
                                                    ->label('Bắt đầu')
                                                    ->native(false),
                                                DatePicker::make('end_date')
                                                    ->label('Kết thúc')
                                                    ->native(false),
                                                TextInput::make('note')
                                                    ->label('Ghi chú')
                                                    ->placeholder('Nhiệm vụ cụ thể...'),
                                            ])
                                            ->addActionLabel('+ Phân công nhân sự'),
                                    ]),

                                Section::make('Lịch trình Thi công & Mốc thời gian (Event Timeline)')
                                    ->description('Theo dõi tiến độ giao hàng, lắp đặt, chạy thử và tháo dỡ màn hình')
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('milestones')
                                            ->relationship('milestones')
                                            ->label('Các mốc thời gian thi công')
                                            ->table([
                                                TableColumn::make('Hạng mục công việc'),
                                                TableColumn::make('Thời gian kế hoạch'),
                                                TableColumn::make('Thời gian thực tế'),
                                                TableColumn::make('Trạng thái'),
                                                TableColumn::make('Ghi chú'),
                                            ])
                                            ->schema([
                                                Select::make('type')
                                                    ->label('Hạng mục')
                                                    ->options(MilestoneType::class)
                                                    ->required(),
                                                DateTimePicker::make('planned_at')
                                                    ->label('Kế hoạch')
                                                    ->native(false)
                                                    ->required(),
                                                DateTimePicker::make('actual_at')
                                                    ->label('Thực tế')
                                                    ->native(false),
                                                Select::make('status')
                                                    ->label('Trạng thái')
                                                    ->options(MilestoneStatus::class)
                                                    ->default(MilestoneStatus::Pending)
                                                    ->required(),
                                                TextInput::make('note')
                                                    ->label('Ghi chú')
                                                    ->placeholder('Ghi chú tiến độ...'),
                                            ])
                                            ->addActionLabel('+ Thêm mốc lịch trình'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    /**
     * Validate that enough assets are available for the requested BOM in the
     * given date window. Throws ValidationException (with a readable conflict
     * list) when stock is insufficient — this is the booking/overbooking guard
     * required by the business docs (Khóa giữ kho / Cảnh báo xung đột lịch).
     *
     * @param  array<string, mixed>  $data
     */
    public static function validateAvailability(array $data, ?int $excludeOrderId = null): void
    {
        $items = $data['items'] ?? [];
        $from = $data['request_date'] ?? null;
        $to = $data['expected_return_date'] ?? $from;
        $warehouseId = $data['warehouse_id'] ?? null;

        if (! $from || empty($items)) {
            return;
        }

        $bom = [];

        foreach ($items as $item) {
            if (empty($item['device_type_id']) || empty($item['quantity_required'])) {
                continue;
            }

            $bom[] = [
                'device_type_id' => (int) $item['device_type_id'],
                'quantity' => (int) $item['quantity_required'],
            ];
        }

        if (empty($bom)) {
            return;
        }

        $result = app(AvailabilityService::class)->checkBomAvailability(
            $bom,
            $from,
            $to,
            $warehouseId ? (int) $warehouseId : null,
            $excludeOrderId,
        );

        if ($result['has_conflicts']) {
            $messages = collect($result['conflicts'])
                ->map(fn (array $c): string => "• {$c['device_type_name']}: cần {$c['requested']}, chỉ còn {$c['available']} (thiếu {$c['shortage']})")
                ->implode("\n");

            throw ValidationException::withMessages([
                'items' => "Thiếu thiết bị khả dụng trong khoảng ngày đã chọn:\n{$messages}",
            ]);
        }
    }
}
