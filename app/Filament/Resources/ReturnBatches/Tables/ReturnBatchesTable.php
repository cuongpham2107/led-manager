<?php

namespace App\Filament\Resources\ReturnBatches\Tables;

use App\Enums\ReturnBatchStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReturnBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['checkoutBatch.order.customer', 'creator', 'items.asset']))
            ->columns([
                TextColumn::make('code')
                    ->label('Mã đợt trả')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('checkoutBatch.order.order_no')
                    ->label('Đơn hàng')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('checkoutBatch.order.customer.name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('checkoutBatch.code')
                    ->label('Phiếu xuất kho')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('return_date')
                    ->label('Ngày trả')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('SL thiết bị')
                    ->suffix(' cabin')
                    ->sortable(),
                TextColumn::make('checkoutBatch.warehouse.name')
                    ->label('Kho hàng')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw("CASE return_batches.status
                            WHEN 'pending' THEN 1
                            WHEN 'in_progress' THEN 2
                            WHEN 'completed' THEN 3
                            ELSE 4
                        END {$direction}");
                    }),
                TextColumn::make('creator.name')
                    ->label('Người nhận')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Hoàn thành')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(ReturnBatchStatus::class),
                SelectFilter::make('warehouse_id')
                    ->label('Kho hàng')
                    ->relationship('checkoutBatch.warehouse', 'name')
                    ->hidden(fn (): bool => (bool) auth()->user()?->getScopedWarehouseId()),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                EditAction::make(),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('status', 'asc');
    }
}
