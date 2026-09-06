<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin kho hàng')
                    ->description('Quản lý chi nhánh kho lưu trữ thiết bị LED')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('name')
                                ->label('Tên kho hàng')
                                ->required()
                                ->placeholder('VD: Kho Hà Nội (Tổng kho)'),
                            TextInput::make('code')
                                ->label('Mã kho')
                                ->required()
                                ->placeholder('VD: WH-HN'),
                            TextInput::make('phone')
                                ->label('Số điện thoại kho')
                                ->tel()
                                ->placeholder('VD: 024 3987 6543'),
                        ]),
                        Textarea::make('address')
                            ->label('Địa chỉ kho hàng')
                            ->rows(2)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Kho đang hoạt động')
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make('Vị trí trong kho (Khu vực / Kệ / Dãy)')
                    ->description('Định nghĩa các vị trí lưu trữ thiết bị trực tiếp trong kho hàng này')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('locations')
                            ->relationship('locations')
                            ->label('Danh sách vị trí')
                            ->addActionLabel('+ Thêm vị trí kho')
                            ->table([
                                TableColumn::make('Mã vị trí')
                                    ->width('20%'),
                                TableColumn::make('Tên vị trí / Kệ')
                                    ->width('35%')
                                    ->markAsRequired(),
                                TableColumn::make('Mô tả / Ghi chú')
                                    ->width('35%'),
                                TableColumn::make('Hoạt động')
                                    ->width('10%')
                                    ->alignCenter(),
                            ])
                            ->schema([
                                TextInput::make('code')
                                    ->placeholder('VD: HN-K1, K01'),
                                TextInput::make('name')
                                    ->required()
                                    ->placeholder('VD: Khu 1 - Kệ Module P2.6'),
                                TextInput::make('description')
                                    ->placeholder('Ghi chú khu vực lưu trữ...'),
                                Toggle::make('is_active')
                                    ->default(true),
                            ])
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
