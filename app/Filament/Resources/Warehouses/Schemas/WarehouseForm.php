<?php

namespace App\Filament\Resources\Warehouses\Schemas;

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
            ]);
    }
}
