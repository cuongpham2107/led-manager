<?php

namespace App\Filament\Resources\CheckinBatches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CheckinBatchForm
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
                                Section::make('Thông tin đợt nhập kho')
                                    ->description('Quản lý nhập mới thiết bị LED hoặc phụ kiện vào kho')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('code')
                                                    ->label('Mã đợt nhập')
                                                    ->required()
                                                    ->placeholder('VD: IN-2608-01'),
                                                Select::make('warehouse_id')
                                                    ->label('Kho nhận hàng')
                                                    ->relationship('warehouse', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                Select::make('batch_type')
                                                    ->label('Loại nhập')
                                                    ->options([
                                                        'production' => 'Sản xuất',
                                                        'purchase' => 'Mua hàng',
                                                        'transfer' => 'Chuyển kho',
                                                    ])
                                                    ->required()
                                                    ->default('production'),
                                                Select::make('product_line_id')
                                                    ->label('Dòng sản phẩm')
                                                    ->relationship('productLine', 'name')
                                                    ->searchable()
                                                    ->preload(),
                                                Select::make('device_type_id')
                                                    ->label('Loại thiết bị')
                                                    ->relationship('deviceType', 'name')
                                                    ->searchable()
                                                    ->preload(),
                                                TextInput::make('quantity')
                                                    ->label('Số lượng dự kiến')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('VD: 100'),
                                                DatePicker::make('expected_date')
                                                    ->label('Ngày dự kiến hàng về')
                                                    ->native(false),
                                                Select::make('status')
                                                    ->label('Trạng thái đợt nhập')
                                                    ->options([
                                                        'pending' => 'Chờ nhận hàng (Pending)',
                                                        'in_progress' => 'Đang quét nhập PDA (In Progress)',
                                                        'completed' => 'Đã nhập kho hoàn tất (Completed)',
                                                        'cancelled' => 'Đã hủy (Cancelled)',
                                                    ])
                                                    ->required()
                                                    ->default('pending'),
                                                DateTimePicker::make('completed_at')
                                                    ->label('Thời gian hoàn thành nhập')
                                                    ->native(false),
                                                Select::make('created_by')
                                                    ->label('Người tạo phiếu')
                                                    ->relationship('creator', 'name')
                                                    ->searchable()
                                                    ->preload(),
                                            ]),
                                        Textarea::make('note')
                                            ->label('Ghi chú đợt nhập hàng')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        Textarea::make('production_note')
                                            ->label('Ghi chú sản xuất')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ================= RIGHT COLUMN: Actual Devices Imported (7 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 7])
                            ->schema([
                                Section::make('Danh sách thiết bị thực tế nhập kho (Quét mã QR / Serial)')
                                    ->description('Các thiết bị thực tế thuộc đợt nhập này. Mặc định lấy từ các đợt sản xuất/mua hàng; có thể bổ sung tay tại đây.')
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship('items')
                                            ->label('Thiết bị nhập kho')
                                            ->table([
                                                TableColumn::make('Mã Serial / Thiết bị'),
                                                TableColumn::make('Tình trạng'),
                                                TableColumn::make('Đã quét'),
                                                TableColumn::make('Người nhận'),
                                                TableColumn::make('Ghi chú tình trạng'),
                                            ])
                                            ->schema([
                                                Select::make('asset_id')
                                                    ->label('Thiết bị trong kho')
                                                    ->relationship('asset', 'serial_no')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->columnSpan(2),
                                                Select::make('condition')
                                                    ->label('Tình trạng')
                                                    ->options([
                                                        'ok' => 'OK (đạt)',
                                                        'fault' => 'Lỗi / hư hỏng',
                                                    ])
                                                    ->default('ok')
                                                    ->required(),
                                                Toggle::make('is_received')
                                                    ->label('Đã quét nhập')
                                                    ->default(true)
                                                    ->inline(false),
                                                Select::make('received_by')
                                                    ->label('Người nhận')
                                                    ->relationship('receivedByUser', 'name')
                                                    ->searchable()
                                                    ->preload(),
                                                TextInput::make('condition_note')
                                                    ->label('Ghi chú')
                                                    ->placeholder('VD: trầy nhẹ 1 góc...'),
                                            ])
                                            ->addActionLabel('+ Thêm thiết bị vào đợt nhập')
                                            ->collapsible(false)
                                            ->reorderable(false),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
