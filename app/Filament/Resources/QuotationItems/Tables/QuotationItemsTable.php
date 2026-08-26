<?php

namespace App\Filament\Resources\QuotationItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuotationItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation.code')
                    ->label('Mã báo giá')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('deviceType.name')
                    ->label('Thiết bị / Vật tư')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('quantity')
                    ->label('Số lượng')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('deviceType.unit')
                    ->label('ĐVT')
                    ->badge(),
                TextColumn::make('unit_cost')
                    ->label('Đơn giá')
                    ->money('VND')
                    ->sortable(),
                TextColumn::make('line_total')
                    ->label('Thành tiền')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('description')
                    ->label('Ghi chú quy cách')
                    ->placeholder('—')
                    ->searchable(),
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
