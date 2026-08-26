<?php

namespace App\Filament\Resources\CheckinBatchItems\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CheckinBatchItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('checkin_batch_id')
                    ->relationship('checkinBatch', 'id')
                    ->required(),
                Select::make('asset_id')
                    ->relationship('asset', 'id')
                    ->required(),
                TextInput::make('condition'),
                Textarea::make('condition_note')
                    ->columnSpanFull(),
                Toggle::make('is_received')
                    ->required(),
                TextInput::make('received_by')
                    ->numeric(),
                DateTimePicker::make('received_at'),
            ]);
    }
}
