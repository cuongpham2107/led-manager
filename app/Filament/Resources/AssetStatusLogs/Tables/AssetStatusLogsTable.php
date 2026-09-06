<?php

namespace App\Filament\Resources\AssetStatusLogs\Tables;

use App\Enums\AssetStatus;
use App\Models\Warehouse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetStatusLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset.serial_no')
                    ->label('Mã Serial')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('from_status')
                    ->label('Trạng thái trước')
                    ->badge()
                    ->sortable(),
                TextColumn::make('to_status')
                    ->label('Trạng thái mới')
                    ->badge()
                    ->sortable(),
                TextColumn::make('fromWarehouse.name')
                    ->label('Từ kho')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('toWarehouse.name')
                    ->label('Đến kho')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('changedBy.name')
                    ->label('Người thực hiện')
                    ->placeholder('Hệ thống')
                    ->searchable(),
                TextColumn::make('note')
                    ->label('Ghi chú luân chuyển')
                    ->limit(30)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Kho hàng')
                    ->options(fn () => Warehouse::query()->pluck('name', 'id')->toArray())
                    ->query(fn ($query, $data) => filled($data['value'] ?? null) ? $query->where(fn ($q) => $q->where('from_warehouse_id', $data['value'])->orWhere('to_warehouse_id', $data['value'])->orWhereHas('asset', fn ($aq) => $aq->where('current_warehouse_id', $data['value']))) : null)
                    ->hidden(fn (): bool => (bool) auth()->user()?->getScopedWarehouseId()),
                SelectFilter::make('to_status')
                    ->label('Trạng thái mới')
                    ->options(AssetStatus::class),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
