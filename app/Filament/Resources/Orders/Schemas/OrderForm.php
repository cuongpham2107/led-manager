<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\AssignmentRole;
use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\AvailabilityService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin đơn hàng & Khách hàng')
                    ->description('Mã đơn hàng, khách hàng, kho xuất và người phụ trách')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('order_no')
                                ->label('Mã đơn hàng')
                                ->default(fn () => 'ORD-'.date('ym').'-'.str_pad((string) (Order::count() + 1), 2, '0', STR_PAD_LEFT))
                                ->required(),
                            Select::make('customer_id')
                                ->label('Khách hàng')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('warehouse_id')
                                ->label('Kho xuất hàng')
                                ->relationship('warehouse', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('quotation_id')
                                ->label('Từ báo giá gốc')
                                ->relationship('quotation', 'code')
                                ->searchable()
                                ->preload()
                                ->placeholder('— Đơn tạo trực tiếp —'),
                            Select::make('status')
                                ->label('Trạng thái đơn hàng')
                                ->options(OrderStatus::class)
                                ->default(OrderStatus::Draft)
                                ->required(),
                            Select::make('sales_user_id')
                                ->label('Sales phụ trách')
                                ->relationship('salesUser', 'name')
                                ->default(fn () => Auth::id())
                                ->searchable()
                                ->preload(),
                        ]),
                    ]),

                Section::make('Chi tiết sự kiện & Giá trị đơn hàng')
                    ->description('Tên sự kiện, thời gian thi công, diện tích và giá trị hợp đồng')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('event')
                                ->label('Tên sự kiện')
                                ->placeholder('VD: Lễ Ra Mắt Xe Điện VinFast VF3'),
                            TextInput::make('value')
                                ->label('Tổng giá trị đơn hàng')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(',')
                                ->numeric()
                                ->suffix(' đ')
                                ->default(0),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('request_date')
                                ->label('Ngày yêu cầu xuất kho')
                                ->native(false)
                                ->required(),
                            DatePicker::make('expected_return_date')
                                ->label('Ngày dự kiến thu hồi')
                                ->native(false),
                            TextInput::make('area_m2')
                                ->label('Diện tích LED (m²)')
                                ->numeric()
                                ->suffix('m²')
                                ->placeholder('VD: 21.00'),
                        ]),
                        Textarea::make('note')
                            ->label('Ghi chú vận hành & thi công')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Danh mục thiết bị cần xuất kho (Bill of Materials - Order Items)')
                    ->description('Định mức toàn bộ thiết bị cần xuất kho cho đơn hàng thuê này')
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
                            ->reorderable()
                            ->cloneable()
                            ->collapsible(false),
                    ]),

                Section::make('Phân công Đội ngũ Kỹ thuật & Nhân sự (Event Crew)')
                    ->description('Gán nhân sự phụ trách thi công, vận hành, tài xế và nhân công sự kiện')
                    ->schema([
                        Repeater::make('assignments')
                            ->relationship('assignments')
                            ->label('Danh sách nhân sự phân công')
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
                            ->addActionLabel('+ Phân công nhân sự')
                            ->reorderable()
                            ->cloneable(),
                    ]),

                Section::make('Lịch trình Thi công & Mốc thời gian (Event Timeline)')
                    ->description('Theo dõi tiến độ giao hàng, lắp đặt, chạy thử và tháo dỡ màn hình')
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
                            ->addActionLabel('+ Thêm mốc lịch trình')
                            ->reorderable()
                            ->cloneable(),
                    ]),
            ]);
    }
}
