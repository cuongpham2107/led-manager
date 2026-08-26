<?php

namespace App\Filament\Resources\RepairLogs\Schemas;

use App\Models\RepairLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RepairLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('asset.id')
                    ->label('Asset'),
                TextEntry::make('start_date')
                    ->date(),
                TextEntry::make('end_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('repair_note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('result_status')
                    ->placeholder('-'),
                TextEntry::make('repair_cost')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (RepairLog $record): bool => $record->trashed()),
            ]);
    }
}
