<?php

namespace App\Filament\Resources\CheckoutBatches\Tables;

use App\Enums\BatchStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CheckoutBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã đợt xuất')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('order.order_no')
                    ->label('Đơn hàng')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Kho xuất')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('required_area_m2')
                    ->label('Diện tích')
                    ->suffix(' m²')
                    ->sortable(),
                TextColumn::make('expected_return_date')
                    ->label('Dự kiến trả')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable(),
                TextColumn::make('dispatched_at')
                    ->label('Thời gian xuất')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(BatchStatus::class),
                SelectFilter::make('warehouse_id')
                    ->label('Kho hàng')
                    ->relationship('warehouse', 'name'),
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
