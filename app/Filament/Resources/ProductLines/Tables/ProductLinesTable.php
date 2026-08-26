<?php

namespace App\Filament\Resources\ProductLines\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductLinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('pixel_pitch_unit')
                    ->searchable(),
                TextColumn::make('pixel_pitch')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('environment')
                    ->searchable(),
                TextColumn::make('module_width_mm')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('module_height_mm')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weight_kg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('power_watt')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('brand')
                    ->searchable(),
                TextColumn::make('cabinet_material')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
