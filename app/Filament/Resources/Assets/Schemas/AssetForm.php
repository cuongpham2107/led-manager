<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Enums\AssetStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin định danh thiết bị')
                    ->description('Mã số Serial, mã QR Code và phân loại thiết bị')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('serial_no')
                                ->label('Mã Serial No')
                                ->required()
                                ->placeholder('VD: GE-R26-000101'),
                            TextInput::make('qr_code')
                                ->label('Mã QR Code')
                                ->placeholder('Tự động theo Serial No nếu trống')
                                ->helperText('Mã định danh dùng để in tem QR dán lên thiết bị'),
                            Select::make('device_type_id')
                                ->label('Loại thiết bị')
                                ->relationship('deviceType', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('product_line_id')
                                ->label('Dòng sản phẩm LED')
                                ->relationship('productLine', 'name')
                                ->searchable()
                                ->preload(),
                            TextInput::make('size')
                                ->label('Kích thước / Quy cách')
                                ->placeholder('VD: 0.5×0.5 m / 2U Rack / 6in1 Case'),
                        ]),
                    ]),

                Section::make('Trạng thái & Vị trí kho')
                    ->description('Vị trí kho hiện tại và tình trạng sẵn sàng vận hành')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('current_status')
                                ->label('Trạng thái hiện tại')
                                ->options(AssetStatus::class)
                                ->default(AssetStatus::Ready)
                                ->required(),
                            Select::make('current_warehouse_id')
                                ->label('Kho lưu trữ hiện tại')
                                ->relationship('currentWarehouse', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                    ]),

                Section::make('Hồ sơ tài chính & Ngày sản xuất')
                    ->description('Theo dõi nguyên giá tài sản phục vụ tính ROI & khấu hao')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('manufactured_date')
                                ->label('Ngày sản xuất')
                                ->placeholder('dd/mm/yyyy'),
                            DatePicker::make('purchase_date')
                                ->label('Ngày mua về kho')
                                ->placeholder('dd/mm/yyyy'),
                            TextInput::make('purchase_cost')
                                ->label('Nguyên giá mua')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(',')
                                ->numeric()
                                ->suffix(' đ')
                                ->placeholder('0'),
                        ]),
                        Textarea::make('note')
                            ->label('Ghi chú kỹ thuật')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
