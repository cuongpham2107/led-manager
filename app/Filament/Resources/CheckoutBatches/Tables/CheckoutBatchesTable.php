<?php

namespace App\Filament\Resources\CheckoutBatches\Tables;

use App\Enums\BatchStatus;
use App\Filament\Resources\CheckoutBatches\Actions\CreateReturnBatchAction;
use App\Filament\Resources\CheckoutBatches\Actions\ViewReturnBatchAction;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CheckoutBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['order', 'customer', 'warehouse', 'returnBatches']))
            ->columns([
                TextColumn::make('code')
                    ->color('primary')
                    ->label('Mã đợt xuất')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('order.order_no')
                    ->label('Đơn hàng')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Kho xuất')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('required_area_m2')
                    ->label('Diện tích')
                    ->suffix(' m²')
                    ->numeric(2)
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Tổng diện tích')
                            ->suffix(' m²')
                            ->numeric(2),
                    ),
                TextColumn::make('expected_return_date')
                    ->label('Dự kiến trả')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw("CASE checkout_batches.status
                            WHEN 'pending' THEN 1
                            WHEN 'in_progress' THEN 2
                            WHEN 'dispatched' THEN 3
                            WHEN 'completed' THEN 4
                            WHEN 'cancelled' THEN 5
                            ELSE 6
                        END {$direction}");
                    }),
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
                    ->relationship('warehouse', 'name')
                    ->hidden(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return (bool) $user?->getScopedWarehouseId();
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    CreateReturnBatchAction::make(),
                    ViewReturnBatchAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('status', 'asc');
    }
}
