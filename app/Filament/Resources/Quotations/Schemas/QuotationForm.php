<?php

namespace App\Filament\Resources\Quotations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin chung báo giá')
                    ->description('Mã báo giá, khách hàng, nhân viên phụ trách và trạng thái')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('code')
                                ->label('Mã báo giá')
                                ->required()
                                ->placeholder('VD: QUO-2608-01'),
                            Select::make('customer_id')
                                ->label('Khách hàng')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('sales_user_id')
                                ->label('Sales phụ trách')
                                ->relationship('salesUser', 'name')
                                ->searchable()
                                ->preload(),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('status')
                                ->label('Trạng thái báo giá')
                                ->options([
                                    'draft' => 'Nháp (Draft)',
                                    'sent' => 'Đã gửi khách (Sent)',
                                    'approved' => 'Khách duyệt (Approved)',
                                    'rejected' => 'Từ chối (Rejected)',
                                    'converted' => 'Đã chuyển thành đơn hàng (Converted)',
                                    'expired' => 'Hết hạn (Expired)',
                                ])
                                ->required()
                                ->default('draft'),
                            Select::make('converted_order_id')
                                ->label('Đơn hàng đã chuyển đổi')
                                ->relationship('convertedOrder', 'order_no')
                                ->searchable()
                                ->preload(),
                            TextInput::make('lost_reason')
                                ->label('Lý do thất bại / từ chối')
                                ->placeholder('VD: Giá cao hơn đối thủ...'),
                        ]),
                    ]),

                Section::make('Thông tin sự kiện')
                    ->description('Tên sự kiện, địa điểm và thời gian thuê màn hình')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('event_name')
                                ->label('Tên sự kiện')
                                ->placeholder('VD: Lễ Ra Mắt Xe Điện VinFast VF3'),
                            TextInput::make('location')
                                ->label('Địa điểm tổ chức')
                                ->placeholder('VD: Trung tâm Hội nghị Quốc gia NCC, Hà Nội'),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('event_start_date')
                                ->label('Ngày bắt đầu sự kiện')
                                ->native(false),
                            DatePicker::make('event_end_date')
                                ->label('Ngày kết thúc sự kiện')
                                ->native(false),
                            TextInput::make('rental_days')
                                ->label('Số ngày thuê')
                                ->numeric()
                                ->suffix('ngày')
                                ->placeholder('VD: 3'),
                        ]),
                    ]),

                Section::make('Quy cách & Tính toán kỹ thuật màn hình LED')
                    ->description('Kích thước màn hình LED và ước tính số lượng thiết bị phụ trợ')
                    ->schema([
                        Grid::make(4)->schema([
                            Select::make('product_line_id')
                                ->label('Dòng Module LED')
                                ->relationship('productLine', 'name')
                                ->searchable()
                                ->preload()
                                ->columnSpan(2),
                            TextInput::make('screen_width_m')
                                ->label('Chiều rộng (W)')
                                ->numeric()
                                ->suffix('m')
                                ->placeholder('12.00'),
                            TextInput::make('screen_height_m')
                                ->label('Chiều cao (H)')
                                ->numeric()
                                ->suffix('m')
                                ->placeholder('4.50'),
                        ]),
                        Grid::make(4)->schema([
                            TextInput::make('screen_area_m2')
                                ->label('Tổng diện tích')
                                ->numeric()
                                ->suffix('m²')
                                ->placeholder('54.00'),
                            TextInput::make('estimated_cabinet_qty')
                                ->label('Số lượng Cabinet')
                                ->numeric()
                                ->suffix('tấm')
                                ->placeholder('216'),
                            TextInput::make('estimated_processor_qty')
                                ->label('Số Processor 4K')
                                ->numeric()
                                ->placeholder('2'),
                            TextInput::make('estimated_power_kw')
                                ->label('Công suất điện tải')
                                ->numeric()
                                ->suffix('kW')
                                ->placeholder('90.72'),
                        ]),
                    ]),

                Section::make('Dự toán chi phí & Giá bán')
                    ->description('Cơ cấu chi phí giá vốn, chiết khấu và tổng doanh thu dự kiến')
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('equipment_cost')
                                ->label('Chi phí thiết bị')
                                ->numeric()
                                ->suffix('VNĐ')
                                ->default(0),
                            TextInput::make('labour_cost')
                                ->label('Chi phí nhân công')
                                ->numeric()
                                ->suffix('VNĐ')
                                ->default(0),
                            TextInput::make('transport_cost')
                                ->label('Chi phí vận chuyển')
                                ->numeric()
                                ->suffix('VNĐ')
                                ->default(0),
                            TextInput::make('accessory_cost')
                                ->label('Chi phí phụ kiện')
                                ->numeric()
                                ->suffix('VNĐ')
                                ->default(0),
                        ]),
                        Grid::make(4)->schema([
                            TextInput::make('total_cost')
                                ->label('Tổng giá vốn (COGS)')
                                ->numeric()
                                ->suffix('VNĐ')
                                ->default(0),
                            TextInput::make('discount_amount')
                                ->label('Chiết khấu giảm giá')
                                ->numeric()
                                ->suffix('VNĐ')
                                ->default(0),
                            TextInput::make('total_price')
                                ->label('Tổng giá trị báo giá')
                                ->numeric()
                                ->suffix('VNĐ')
                                ->default(0),
                            TextInput::make('margin_percent')
                                ->label('Biên lợi nhuận (Margin)')
                                ->numeric()
                                ->suffix('%'),
                        ]),
                        Textarea::make('note')
                            ->label('Ghi chú & Điều khoản báo giá')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
