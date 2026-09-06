<?php

namespace App\Filament\Resources\WarehouseLocations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarehouseLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin vị trí kho')
                    ->description('Quản lý khu vực, dãy, tầng kệ lưu trữ trong kho hàng')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('warehouse_id')
                                ->label('Kho hàng')
                                ->relationship('warehouse', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            TextInput::make('code')
                                ->label('Mã vị trí')
                                ->placeholder('VD: HN-K01, K01, A-01'),
                        ]),
                        Grid::make(1)->schema([
                            TextInput::make('name')
                                ->label('Tên vị trí / Khu vực / Kệ')
                                ->required()
                                ->placeholder('VD: Khu A - Kệ 01, Khu test kỹ thuật, Dãy 2 - Tầng 1'),
                        ]),
                        Textarea::make('description')
                            ->label('Mô tả / Ghi chú vị trí')
                            ->rows(2)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Đang sử dụng')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
