<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin đơn hàng & Hợp đồng')
                    ->description('Mã đơn hàng, khách hàng, kho phục vụ và trạng thái xử lý')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('order_no')
                                ->label('Số đơn hàng (Order No)')
                                ->required()
                                ->placeholder('VD: ORD-2608-01'),
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
                                ->label('Từ Báo giá (Quotation)')
                                ->relationship('quotation', 'code')
                                ->searchable()
                                ->preload(),
                            Select::make('sales_user_id')
                                ->label('Sales phụ trách')
                                ->relationship('salesUser', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('status')
                                ->label('Trạng thái đơn hàng')
                                ->options([
                                    'draft' => 'Mới tạo (Draft)',
                                    'outbound_created' => 'Đã tạo đợt xuất kho (Outbound Created)',
                                    'dispatched' => 'Đã xuất kho đi sự kiện (Dispatched)',
                                    'returned' => 'Đã thu hồi về kho (Returned)',
                                    'completed' => 'Hoàn tất đơn hàng (Completed)',
                                    'cancelled' => 'Đã hủy (Cancelled)',
                                ])
                                ->required()
                                ->default('draft'),
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
                                ->numeric()
                                ->suffix('VNĐ')
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
                                ->placeholder('VD: 54.00'),
                        ]),
                        Textarea::make('note')
                            ->label('Ghi chú vận hành & thi công')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
