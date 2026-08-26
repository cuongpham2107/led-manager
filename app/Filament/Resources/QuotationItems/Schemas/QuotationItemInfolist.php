<?php

namespace App\Filament\Resources\QuotationItems\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QuotationItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('quotation.id')
                    ->label('Quotation'),
                TextEntry::make('category'),
                TextEntry::make('deviceType.name')
                    ->label('Device type')
                    ->placeholder('-'),
                TextEntry::make('description'),
                TextEntry::make('quantity')
                    ->numeric(),
                TextEntry::make('unit')
                    ->placeholder('-'),
                TextEntry::make('unit_cost')
                    ->money(),
                TextEntry::make('line_total')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
