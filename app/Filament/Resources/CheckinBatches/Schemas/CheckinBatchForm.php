<?php

namespace App\Filament\Resources\CheckinBatches\Schemas;

use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
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

                        // ================= LEFT COLUMN: Batch Information (4 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 4])
                            ->schema([
                                Section::make('Thông tin đợt nhập kho')
                                    ->description('Quản lý nhập mới thiết bị LED hoặc phụ kiện vào kho')
                                    ->collapsible()
                                    ->schema([
                                        TextInput::make('code')
                                            ->label('Mã đợt nhập')
                                            ->required()
                                            ->placeholder('VD: IN-2608-01')
                                            ->columnSpanFull(),
                                        Select::make('batch_type')
                                            ->label('Loại nhập')
                                            ->options(CheckinBatchType::class)
                                            ->required()
                                            ->default(CheckinBatchType::Production)
                                            ->columnSpanFull(),
                                        Select::make('warehouse_id')
                                            ->label('Kho nhận hàng')
                                            ->relationship('warehouse', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpanFull(),
                                        Grid::make(2)->schema([
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
                                        ]),
                                        Grid::make(2)->schema([
                                            TextInput::make('quantity')
                                                ->label('Số lượng dự kiến')
                                                ->numeric()
                                                ->minValue(1)
                                                ->placeholder('VD: 100'),
                                            DatePicker::make('expected_date')
                                                ->label('Ngày dự kiến')
                                                ->native(false),
                                        ]),
                                        Select::make('status')
                                            ->label('Trạng thái')
                                            ->options(BatchStatus::class)
                                            ->required()
                                            ->default(BatchStatus::Pending)
                                            ->columnSpanFull(),
                                        Grid::make(2)->schema([
                                            Select::make('created_by')
                                                ->label('Người tạo')
                                                ->relationship('creator', 'name')
                                                ->searchable()
                                                ->preload(),
                                            DateTimePicker::make('completed_at')
                                                ->label('Hoàn thành lúc')
                                                ->native(false),
                                        ]),
                                        Textarea::make('note')
                                            ->label('Ghi chú')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        Textarea::make('production_note')
                                            ->label('Ghi chú sản xuất')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ================= RIGHT COLUMN: Actual Devices Imported (8 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 8])
                            ->schema([
                                Section::make('Danh sách thiết bị thực tế nhập kho')
                                    ->description('Mỗi dòng là 1 thiết bị trong đợt nhập. Tình trạng có thể khác nhau từng thiết bị. Người nhận & thời gian quét được ghi tự động khi PDA scan.')
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship('items')
                                            ->label('Thiết bị nhập kho')
                                            ->table([
                                                TableColumn::make('Mã Serial / Thiết bị'),
                                                TableColumn::make('Tình trạng'),
                                                TableColumn::make('Ghi chú tình trạng'),
                                            ])
                                            ->schema([
                                                Select::make('asset_id')
                                                    ->label('Thiết bị trong kho')
                                                    ->relationship('asset', 'serial_no')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),
                                                Select::make('condition')
                                                    ->label('Tình trạng')
                                                    ->options([
                                                        'ok' => 'OK (đạt)',
                                                        'fault' => 'Lỗi / hư hỏng',
                                                    ])
                                                    ->default('ok')
                                                    ->required(),
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
