<?php

namespace App\Filament\Resources\Quotations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('salesUser.name')
                    ->searchable(),
                TextColumn::make('screen_width_m')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('screen_height_m')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('screen_area_m2')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('productLine.name')
                    ->searchable(),
                TextColumn::make('rental_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('event_start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('event_end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('event_name')
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('estimated_cabinet_qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_processor_qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_load_kg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_power_kw')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('equipment_cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('labour_cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('transport_cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('accessory_cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('total_cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('margin_percent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('lost_reason')
                    ->searchable(),
                TextColumn::make('convertedOrder.id')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
