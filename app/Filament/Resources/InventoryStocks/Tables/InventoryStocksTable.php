<?php

namespace App\Filament\Resources\InventoryStocks\Tables;

use App\Enums\AssetStatus;
use App\Models\Agency;
use App\Models\Asset;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
                        AssetStatus::NewlyAdded => '• Mới',
                        AssetStatus::Ready => '• Sẵn sàng',
                        AssetStatus::InEvent => '• Đang giữ',
                        AssetStatus::InTransit => '• Đang vận chuyển',
                        AssetStatus::Repairing => '• Đang sửa',
                        AssetStatus::Disposed => '• Ngừng khai thác',
                        AssetStatus::Missing => '• Chưa trả về',
                    })
                    ->color(fn (AssetStatus $state): string => match ($state) {
                        AssetStatus::NewlyAdded => 'primary',
                        AssetStatus::Ready => 'success',
                        AssetStatus::InEvent, AssetStatus::InTransit => 'warning',
                        AssetStatus::Repairing => 'danger',
                        AssetStatus::Disposed, AssetStatus::Missing => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('currentWarehouse.name')
                    ->label('KHO HÀNG')
                    ->badge()
                    ->color('info')
                    ->placeholder('Chưa gán kho')
                    ->sortable(),

                TextColumn::make('agency')
                    ->label('ĐẠI LÝ / ĐƠN VỊ')
                    ->badge()
                    ->state(fn (Asset $record): string => $record->currentWarehouse?->agency
                        ? "{$record->currentWarehouse->agency->name} ({$record->currentWarehouse->agency->code})"
                        : 'Tổng công ty (HQ)'
                    )
                    ->color(fn (Asset $record): string => $record->currentWarehouse?->agency ? 'warning' : 'gray')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->select('assets.*')
                            ->leftJoin('warehouses', 'warehouses.id', '=', 'assets.current_warehouse_id')
                            ->leftJoin('agencies', 'agencies.warehouse_id', '=', 'warehouses.id')
                            ->orderBy('agencies.name', $direction);
                    }),
            ])
            ->filters([
                SelectFilter::make('current_status')
                    ->label('Trạng thái')
                    ->options(fn (): array => collect(AssetStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])->toArray()),

                SelectFilter::make('product_line_id')
                    ->label('Dòng sản phẩm')
                    ->options(fn (): array => ProductLine::query()->pluck('name', 'id')->toArray()),

                SelectFilter::make('current_warehouse_id')
                    ->label('Kho hàng')
                    ->options(fn (): array => Warehouse::where('is_active', true)->pluck('name', 'id')->toArray())
                    ->hidden(function (): bool {
                        $user = Auth::user();

                        return (bool) ($user instanceof User ? $user->getScopedWarehouseId() : null);
                    }),

                SelectFilter::make('agency_filter')
                    ->label('Đại lý')
                    ->options(fn (): array => ['hq' => '🏢 Tổng công ty (HQ)'] + Agency::where('is_active', true)->pluck('name', 'id')->toArray())
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'hq') {
                            return $query->whereHas('currentWarehouse', fn ($q) => $q->whereDoesntHave('agency'));
                        }

                        return $query->whereHas('currentWarehouse.agency', fn ($q) => $q->where('id', $data['value']));
                    })
                    ->hidden(function (): bool {
                        $user = Auth::user();

                        return (bool) ($user instanceof User ? $user->getScopedAgencyId() : null);
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([])
            ->toolbarActions([]);
    }
}
