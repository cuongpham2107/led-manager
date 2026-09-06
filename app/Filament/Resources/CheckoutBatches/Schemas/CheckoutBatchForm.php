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
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CheckoutBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 12])
                    ->columnSpanFull()
                    ->schema([

                        // ================= LEFT COLUMN: Batch Information (5 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 5])
                            ->schema([
                                Section::make('Thông tin đợt xuất kho đi sự kiện')
                                    ->description('Quản lý thông tin đợt xuất thiết bị theo đơn hàng (Pick & Dispatch)')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
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
                                                Select::make('warehouse_id')
                                                    ->label('Kho xuất hàng')
                                                    ->relationship('warehouse', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                TextInput::make('required_area_m2')
                                                    ->label('Diện tích yêu cầu (m²)')
                                                    ->numeric()
                                                    ->suffix(' m²'),
                                                DatePicker::make('expected_return_date')
                                                    ->label('Ngày dự kiến trả về')
                                                    ->native(false),
                                                Select::make('status')
                                                    ->label('Trạng thái đợt xuất')
                                                    ->options(BatchStatus::class)
                                                    ->required()
                                                    ->default(BatchStatus::Pending),
                                                DateTimePicker::make('dispatched_at')
                                                    ->label('Thời gian xuất kho')
                                                    ->native(false),
                                                Select::make('created_by')
                                                    ->label('Thủ kho tạo phiếu')
                                                    ->relationship('creator', 'name')
                                                    ->default(fn () => Auth::id())
                                                    ->searchable()
                                                    ->preload()
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),

                        // ================= RIGHT COLUMN: Scan / Checklist Devices (7 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 7])
                            ->schema([
                                Section::make('Danh sách thiết bị thực tế xuất kho (Quét mã QR / Serial)')
                                    ->description('Chọn hoặc quét mã QR/Serial từng thiết bị trong kho để xuất đi sự kiện')
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship('items')
                                            ->label('Thiết bị quét xuất')
                                            ->table([
                                                TableColumn::make('Mã Serial / Thiết bị')
                                                    ->width('50%'),
                                                TableColumn::make('Đã quét')
                                                    ->width('15%'),
                                                TableColumn::make('Ghi chú')
                                                    ->width('35%'),
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
                                                TextInput::make('note')
                                                    ->label('Ghi chú')
                                                    ->placeholder('Ghi chú nếu có...'),
                                            ])
                                            ->addActionLabel('+ Quét / Thêm thiết bị vào đợt xuất')
                                            ->collapsible(false)
                                            ->reorderable(false),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
