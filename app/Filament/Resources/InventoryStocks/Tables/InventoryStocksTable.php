<?php

namespace App\Filament\Resources\InventoryStocks\Tables;

use App\Enums\AssetStatus;
use App\Models\ProductLine;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryStocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serial_no')
                    ->label('SỐ SERI')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('productLine.name')
                    ->label('DÒNG SẢN PHẨM')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('current_status')
                    ->label('TRẠNG THÁI')
                    ->badge()
                    ->formatStateUsing(fn (AssetStatus $state): string => match ($state) {
                        AssetStatus::Ready => '• Sẵn sàng',
                        AssetStatus::InEvent => '• Đang giữ',
                        AssetStatus::InTransit => '• Đang vận chuyển',
                        AssetStatus::Repairing => '• Đang sửa',
                        AssetStatus::Disposed => '• Ngừng khai thác',
                        AssetStatus::Missing => '• Chưa trả về',
                    })
                    ->color(fn (AssetStatus $state): string => match ($state) {
                        AssetStatus::Ready => 'success',
                        AssetStatus::InEvent, AssetStatus::InTransit => 'warning',
                        AssetStatus::Repairing => 'danger',
                        AssetStatus::Disposed, AssetStatus::Missing => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('location_label')
                    ->label('VỊ TRÍ')
                    ->badge()
                    ->color('info')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->select('assets.*')
                            ->leftJoin('warehouses', 'warehouses.id', '=', 'assets.current_warehouse_id')
                            ->orderBy('warehouses.name', $direction);
                    }),
            ])
            ->filters([
                SelectFilter::make('current_status')
                    ->label('Trạng thái')
                    ->options(fn (): array => collect(AssetStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])->toArray()),
                SelectFilter::make('product_line_id')
                    ->label('Dòng sản phẩm')
                    ->options(fn (): array => ProductLine::query()->pluck('name', 'id')->toArray()),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([])
            ->toolbarActions([]);
    }
}
