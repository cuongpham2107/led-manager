<?php

namespace App\Filament\Resources\CheckoutBatches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CheckoutBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin đợt xuất kho đi sự kiện')
                    ->description('Quản lý quét xuất thiết bị theo đơn hàng (Pick & Dispatch)')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('code')
                                ->label('Mã đợt xuất')
                                ->required()
                                ->placeholder('VD: OUT-2608-01'),
                            Select::make('order_id')
                                ->label('Đơn hàng liên kết')
                                ->relationship('order', 'order_no')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('customer_id')
                                ->label('Khách hàng')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('warehouse_id')
                                ->label('Kho xuất hàng')
                                ->relationship('warehouse', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('required_area_m2')
                                ->label('Diện tích yêu cầu (m²)')
                                ->numeric()
                                ->suffix('m²'),
                            DatePicker::make('expected_return_date')
                                ->label('Ngày dự kiến trả về')
                                ->native(false),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('status')
                                ->label('Trạng thái đợt xuất')
                                ->options([
                                    'pending' => 'Chờ quét xuất (Pending)',
                                    'in_progress' => 'Đang quét chọn Serial (In Progress)',
                                    'dispatched' => 'Đã xuất kho đi sự kiện (Dispatched)',
                                    'cancelled' => 'Đã hủy (Cancelled)',
                                ])
                                ->required()
                                ->default('pending'),
                            Select::make('created_by')
                                ->label('Thủ kho tạo phiếu')
                                ->relationship('creator', 'name')
                                ->searchable()
                                ->preload(),
                            DateTimePicker::make('dispatched_at')
                                ->label('Thời gian xuất kho')
                                ->native(false),
                        ]),
                    ]),
            ]);
    }
}
