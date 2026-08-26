<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin định danh thiết bị')
                    ->description('Mã số Serial, mã QR Code và phân loại thiết bị')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('serial_no')
                                ->label('Mã Serial No')
                                ->required()
                                ->placeholder('VD: GE-R26-000101'),
                            TextInput::make('qr_code')
                                ->label('Mã QR Code')
                                ->placeholder('VD: QR-GE-R26-000101'),
                            Select::make('device_type_id')
                                ->label('Loại thiết bị')
                                ->relationship('deviceType', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('product_line_id')
                                ->label('Dòng sản phẩm LED (nếu là Cabinet)')
                                ->relationship('productLine', 'name')
                                ->searchable()
                                ->preload(),
                            TextInput::make('size')
                                ->label('Kích thước / Quy cách')
                                ->placeholder('VD: 500x500mm / 2U Rack / 8in1 Case'),
                        ]),
                    ]),

                Section::make('Trạng thái & Vị trí kho')
                    ->description('Vị trí kho hiện tại và tình trạng sẵn sàng vận hành')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('current_status')
                                ->label('Trạng thái hiện tại')
                                ->options([
                                    'ready' => 'Sẵn sàng (Ready)',
                                    'in_event' => 'Đang chạy sự kiện (In Event)',
                                    'in_transit' => 'Đang vận chuyển (In Transit)',
                                    'repairing' => 'Đang bảo dưỡng / sửa chữa (Repairing)',
                                    'disposed' => 'Đã thanh lý (Disposed)',
                                ])
                                ->required()
                                ->default('ready'),
                            Select::make('current_warehouse_id')
                                ->label('Kho hiện tại')
                                ->relationship('currentWarehouse', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                    ]),

                Section::make('Xuất xứ & Chi phí mua')
                    ->description('Ngày sản xuất, ngày mua và nguyên giá tài sản')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('purchase_cost')
                                ->label('Nguyên giá mua')
                                ->numeric()
                                ->suffix('VNĐ')
                                ->placeholder('VD: 8,500,000'),
                            DatePicker::make('purchase_date')
                                ->label('Ngày mua')
                                ->native(false),
                            DatePicker::make('manufactured_date')
                                ->label('Ngày sản xuất')
                                ->native(false),
                        ]),
                        Textarea::make('note')
                            ->label('Ghi chú tình trạng thiết bị')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
