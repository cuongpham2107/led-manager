<?php

namespace App\Filament\Resources\WarehouseLocations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WarehouseLocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('KHO HÀNG')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('MÃ VỊ TRÍ')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->placeholder('—'),

                TextColumn::make('name')
                    ->label('TÊN VỊ TRÍ')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('assets_count')
                    ->counts('assets')
                    ->label('THIẾT BỊ LƯU TRỮ')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('MÔ TẢ')
                    ->limit(35)
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('HOẠT ĐỘNG')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Kho hàng')
                    ->relationship('warehouse', 'name')
                    ->preload(),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật vị trí kho')
                    ->modalWidth(Width::Large),
                DeleteAction::make(),
            ], position: RecordActionsPosition::AfterCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
