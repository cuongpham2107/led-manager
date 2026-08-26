<?php

namespace App\Filament\Resources\ReturnBatchItems\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReturnBatchItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('return_batch_id')
                    ->relationship('returnBatch', 'id')
                    ->required(),
                Select::make('asset_id')
                    ->relationship('asset', 'id')
                    ->required(),
                Select::make('checkout_batch_item_id')
                    ->relationship('checkoutBatchItem', 'id'),
                TextInput::make('grade'),
                Textarea::make('grade_note')
                    ->columnSpanFull(),
                Toggle::make('is_received')
                    ->required(),
                TextInput::make('received_by')
                    ->numeric(),
                DateTimePicker::make('received_at'),
            ]);
    }
}
