<?php

namespace App\Filament\Resources\OrderItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Select::make('product_line_id')
                    ->relationship('productLine', 'name'),
                TextInput::make('quantity_required')
                    ->required()
                    ->numeric(),
                TextInput::make('unit_price')
                    ->numeric()
                    ->prefix('$'),
                Textarea::make('note')
                    ->columnSpanFull(),
            ]);
    }
}
