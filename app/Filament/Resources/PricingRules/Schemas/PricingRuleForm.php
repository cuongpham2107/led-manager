<?php

namespace App\Filament\Resources\PricingRules\Schemas;

use App\Enums\CustomerType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class PricingRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cấu hình định giá')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('product_line_id')
                                    ->label('Dòng LED')
                                    ->relationship('productLine', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('customer_type')
                                    ->label('Nhóm khách hàng')
                                    ->options(CustomerType::class)
                                    ->placeholder('Tất cả nhóm khách hàng'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('base_price_per_unit_per_day')
                                    ->label('Đơn giá thuê / tấm / ngày')
                                    ->required()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->default(400000),
                                TextInput::make('min_days')
                                    ->label('Từ ngày thuê thứ')
                                    ->required()
                                    ->numeric()
                                    ->default(1),
                                TextInput::make('max_days')
                                    ->label('Đến ngày thuê thứ')
                                    ->numeric()
                                    ->placeholder('Không giới hạn'),
                            ]),
                        Grid::make(4)
                            ->schema([
                                TextInput::make('discount_percent')
                                    ->label('Chiết khấu (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0),
                                TextInput::make('crew_rate_per_person_per_day')
                                    ->label('Chi phí nhân công / người / ngày')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->default(1600000),
                                TextInput::make('transport_rate_per_km')
                                    ->label('Đơn giá vận chuyển / km')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->default(28000),
                                TextInput::make('accessory_rate_per_m2')
                                    ->label('Phụ kiện & vật tư / m²')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->default(50000),
                            ]),
                        Toggle::make('is_active')
                            ->label('Đang áp dụng')
                            ->default(true),
                    ]),
            ]);
    }
}
