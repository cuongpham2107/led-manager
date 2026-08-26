<?php

namespace App\Filament\Resources\CheckoutBatches\Schemas;

use App\Enums\BatchStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

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
                                ->options(BatchStatus::class)
                                ->required()
                                ->default(BatchStatus::Pending),
                            Select::make('created_by')
                                ->label('Thủ kho tạo phiếu')
                                ->relationship('creator', 'name')
                                ->default(fn () => Auth::id())
                                ->searchable()
                                ->preload(),
                            DateTimePicker::make('dispatched_at')
                                ->label('Thời gian xuất kho')
                                ->native(false),
                        ]),
                    ]),

                Section::make('Danh sách thiết bị thực tế xuất kho (Quét mã QR / Serial)')
                    ->description('Chọn hoặc quét mã QR/Serial từng thiết bị trong kho để xuất đi sự kiện')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label('Thiết bị quét xuất')
                            ->table([
                                TableColumn::make('Mã Serial / Thiết bị'),
                                TableColumn::make('Đã quét'),
                                TableColumn::make('Độ sáng'),
                                TableColumn::make('Điểm LED'),
                                TableColumn::make('Màu sắc'),
                                TableColumn::make('Nguồn điện'),
                                TableColumn::make('Ghi chú kiểm tra'),
                            ])
                            ->schema([
                                Select::make('asset_id')
                                    ->label('Thiết bị trong kho')
                                    ->relationship('asset', 'serial_no')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Toggle::make('is_dispatched')
                                    ->label('Đã quét')
                                    ->default(true),
                                Toggle::make('checked_brightness')
                                    ->label('Độ sáng')
                                    ->default(true),
                                Toggle::make('checked_dead_pixels')
                                    ->label('Điểm LED')
                                    ->default(true),
                                Toggle::make('checked_color')
                                    ->label('Màu sắc')
                                    ->default(true),
                                Toggle::make('checked_power')
                                    ->label('Nguồn')
                                    ->default(true),
                                TextInput::make('checklist_note')
                                    ->label('Ghi chú')
                                    ->placeholder('Ghi chú tình trạng...'),
                            ])
                            ->addActionLabel('+ Quét / Thêm thiết bị vào đợt xuất')
                            ->collapsible(false)
                            ->reorderable(false),
                    ]),
            ]);
    }
}
