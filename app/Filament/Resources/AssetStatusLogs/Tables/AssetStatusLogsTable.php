<?php

namespace App\Filament\Resources\AssetStatusLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetStatusLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset.id')
                    ->searchable(),
                TextColumn::make('from_status')
                    ->searchable(),
                TextColumn::make('to_status')
                    ->searchable(),
                TextColumn::make('fromWarehouse.name')
                    ->searchable(),
                TextColumn::make('toWarehouse.name')
                    ->searchable(),
                TextColumn::make('source_type')
                    ->searchable(),
                TextColumn::make('source_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('changed_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
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
