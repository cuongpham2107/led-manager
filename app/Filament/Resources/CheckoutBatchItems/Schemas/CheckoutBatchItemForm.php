<?php

namespace App\Filament\Resources\CheckoutBatchItems\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CheckoutBatchItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('checkout_batch_id')
                    ->relationship('checkoutBatch', 'id')
                    ->required(),
                Select::make('asset_id')
                    ->relationship('asset', 'id')
                    ->required(),
                Toggle::make('is_dispatched')
                    ->required(),
                TextInput::make('dispatched_by')
                    ->numeric(),
                DateTimePicker::make('dispatched_at'),
            ]);
    }
}
