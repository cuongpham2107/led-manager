<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Enums\AssetStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
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
                        Grid::make(2)->schema([
                            TextInput::make('serial_no')
                                ->label('Mã Serial No')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    if ($state && empty($get('qr_code'))) {
                                        $set('qr_code', "LED-{$state}");
                                    }
                                })
                                ->placeholder('VD: GE-R26-000101'),
                            TextInput::make('qr_code')
                                ->label('Mã QR Code')
                                ->disabled()
                                ->dehydrated()
                                ->placeholder('Hệ thống tự động sinh (LED-{Số Seri})')
                                ->helperText('Mã QR tự động sinh theo Serial No, không cần nhập thủ công.'),
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
                        Grid::make(3)->schema([
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
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('warehouse_location_id', null))
                                ->required(),
                            Select::make('warehouse_location_id')
                                ->label('Vị trí trong kho')
                                ->relationship('warehouseLocation', 'name', modifyQueryUsing: function ($query, callable $get) {
                                    if ($whId = $get('current_warehouse_id')) {
                                        $query->where('warehouse_id', $whId);
                                    }
                                })
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->placeholder('Chọn vị trí kho...'),
                        ]),
                    ]),

                Section::make('Hồ sơ vận hành & Tài chính')
                    ->description('Theo dõi thông số chạy, số lần cho thuê, nguyên giá tài sản và khấu hao')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('operating_hours')
                                ->label('Số giờ chạy')
                                ->numeric()
                                ->minValue(0)
                                ->suffix('h')
                                ->default(0)
                                ->placeholder('0')
                                ->helperText('Tổng số giờ thiết bị đã vận hành thực tế.'),
                            TextInput::make('rental_count')
                                ->label('Số lần cho thuê')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->placeholder('0')
                                ->helperText('Tổng số lượt thiết bị được điều động đi sự kiện.'),
                        ]),
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

                Section::make('Lịch sử làm việc của tài sản')
                    ->description('Nhật ký các đợt xuất kho đi sự kiện, khách hàng và đánh giá kiểm định hoàn trả')
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        ViewField::make('working_history')
                            ->view('filament.components.asset-working-history')
                            ->viewData(function ($record) {
                                if (! $record) {
                                    return [
                                        'history' => collect(),
                                        'repairLogs' => collect(),
                                        'record' => null,
                                    ];
                                }

                                $history = $record->checkoutBatchItems()
                                    ->with([
                                        'checkoutBatch.order.customer',
                                        'checkoutBatch.order.salesUser',
                                        'checkoutBatch.warehouse',
                                        'returnBatchItem.returnBatch',
                                    ])
                                    ->latest('id')
                                    ->get();

                                $repairLogs = $record->repairLogs()
                                    ->with('creator')
                                    ->latest('start_date')
                                    ->get();

                                return [
                                    'history' => $history,
                                    'repairLogs' => $repairLogs,
                                    'record' => $record,
                                ];
                            })
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
