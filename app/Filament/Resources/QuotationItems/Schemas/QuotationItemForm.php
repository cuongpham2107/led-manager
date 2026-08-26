<?php

namespace App\Filament\Resources\QuotationItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuotationItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('quotation_id')
                    ->relationship('quotation', 'id')
                    ->required(),
                TextInput::make('category')
                    ->required()
                    ->default('equipment'),
                Select::make('device_type_id')
                    ->relationship('deviceType', 'name'),
                TextInput::make('description')
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('unit'),
                TextInput::make('unit_cost')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                TextInput::make('line_total')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
