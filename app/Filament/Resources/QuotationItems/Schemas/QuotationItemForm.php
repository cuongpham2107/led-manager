<?php

namespace App\Filament\Resources\QuotationItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class QuotationItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('quotation_id')
                    ->label('Báo giá')
                    ->relationship('quotation', 'code')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('product_line_id')
                    ->label('Dòng SP LED')
                    ->relationship('productLine', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('quantity')
                    ->label('Số lượng')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('unit_cost')
                    ->label('Đơn giá')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->suffix(' đ')
                    ->default(0),
                TextInput::make('line_total')
                    ->label('Thành tiền')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->suffix(' đ')
                    ->default(0),
                TextInput::make('description')
                    ->label('Ghi chú quy cách')
                    ->placeholder('Ghi chú quy cách...'),
            ]);
    }
}
