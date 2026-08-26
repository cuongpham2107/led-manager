<?php

namespace App\Filament\Resources\ProductLines\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductLineInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('code'),
                TextEntry::make('pixel_pitch_unit'),
                TextEntry::make('pixel_pitch')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('environment'),
                TextEntry::make('module_width_mm')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('module_height_mm')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('weight_kg')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('power_watt')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('brand')
                    ->placeholder('-'),
                TextEntry::make('cabinet_material')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
