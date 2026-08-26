<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Models\Quotation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('customer.name')
                    ->label('Customer'),
                TextEntry::make('salesUser.name')
                    ->label('Sales user')
                    ->placeholder('-'),
                TextEntry::make('screen_width_m')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('screen_height_m')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('screen_area_m2')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('productLine.name')
                    ->label('Product line')
                    ->placeholder('-'),
                TextEntry::make('rental_days')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('event_start_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('event_end_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('event_name')
                    ->placeholder('-'),
                TextEntry::make('location')
                    ->placeholder('-'),
                TextEntry::make('estimated_cabinet_qty')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('estimated_processor_qty')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('estimated_load_kg')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('estimated_power_kw')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('equipment_cost')
                    ->money(),
                TextEntry::make('labour_cost')
                    ->money(),
                TextEntry::make('transport_cost')
                    ->money(),
                TextEntry::make('accessory_cost')
                    ->money(),
                TextEntry::make('total_cost')
                    ->money(),
                TextEntry::make('discount_amount')
                    ->numeric(),
                TextEntry::make('total_price')
                    ->money(),
                TextEntry::make('margin_percent')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('lost_reason')
                    ->placeholder('-'),
                TextEntry::make('convertedOrder.id')
                    ->label('Converted order')
                    ->placeholder('-'),
                TextEntry::make('note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Quotation $record): bool => $record->trashed()),
            ]);
    }
}
