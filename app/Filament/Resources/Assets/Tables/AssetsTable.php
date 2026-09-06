<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\Actions\CompleteMaintenanceBulkAction;
use App\Filament\Resources\Assets\Actions\SendToMaintenanceBulkAction;
use App\Filament\Resources\Assets\Actions\ViewQrCodeAction;
use App\Models\ProductLine;
use App\Models\Warehouse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Tìm theo số seri hoặc vị trí...')
            ->defaultSort('serial_no', 'asc')
            ->columns([
                TextColumn::make('serial_no')
                    ->label('SỐ SERI')
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('serial_no', 'like', "%{$search}%")
                                ->orWhereHas('warehouseLocation', fn ($lq) => $lq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                                ->orWhereHas('currentWarehouse', fn ($wq) => $wq->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('productLine.code')
                    ->label('DÒNG SẢN PHẨM')
                    ->formatStateUsing(fn ($record) => $record->productLine?->code ?: $record->productLine?->name)
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('size')
                    ->label('KÍCH THƯỚC')
                    ->formatStateUsing(fn (?string $state): string => $state ? str_replace('x', '×', $state).(str_ends_with($state, 'm') ? '' : ' m') : '—')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('current_status')
                    ->label('TRẠNG THÁI')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof AssetStatus ? $state->getLabel() : ($state?->value ?? (string) $state))
                    ->color(fn ($state) => $state instanceof AssetStatus ? $state->getColor() : 'gray')
                    ->sortable(),

                TextColumn::make('currentWarehouse.name')
                    ->label('KHO HÀNG')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('warehouseLocation.name')
                    ->label('VỊ TRÍ KHO')
                    ->placeholder('Chưa xếp vị trí')
                    ->sortable(),

                TextColumn::make('operating_hours')
                    ->label('SỐ GIỜ CHẠY')
                    ->formatStateUsing(fn ($state): string => number_format((float) ($state ?: 0), 0, ',', '.').' h')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('rental_count')
                    ->label('SỐ LẦN CHO THUÊ')
                    ->formatStateUsing(fn ($state): string => number_format((int) ($state ?: 0), 0, ',', '.'))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('manufactured_date')
                    ->label('NGÀY SẢN XUẤT')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('current_warehouse_id')
                    ->label('Kho hàng')
                    ->options(fn (): array => Warehouse::query()->pluck('name', 'id')->toArray())
                    ->hidden(fn (): bool => (bool) auth()->user()?->getScopedWarehouseId()),

                SelectFilter::make('warehouse_location_id')
                    ->label('Vị trí kho')
                    ->relationship('warehouseLocation', 'name', modifyQueryUsing: function ($query, $livewire) {
                        $whId = auth()->user()?->getScopedWarehouseId()
                            ?: ($livewire?->getTableFilterState('current_warehouse_id')['value'] ?? null);

                        if ($whId) {
                            $query->where('warehouse_id', $whId);
                        }
                    })
                    ->preload(),

                SelectFilter::make('current_status')
                    ->label('Tất cả trạng thái')
                    ->options(fn (): array => collect(AssetStatus::cases())->mapWithKeys(fn (AssetStatus $status): array => [$status->value => $status->getLabel()])->toArray()),

                SelectFilter::make('product_line_id')
                    ->label('Tất cả dòng sản phẩm')
                    ->options(fn (): array => ProductLine::query()->pluck('name', 'id')->toArray()),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                ViewQrCodeAction::make()
                    ->icon('heroicon-o-squares-2x2')
                    ->iconButton()
                    ->tooltip('Xem mã QR'),

                EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->iconButton()
                    ->modalHeading('Cập nhật thông tin tài sản LED')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->tooltip('Chỉnh sửa'),

                DeleteAction::make()
                    ->icon('heroicon-o-x-mark')
                    ->iconButton()
                    ->color('danger')
                    ->tooltip('Xóa'),
            ], position: RecordActionsPosition::AfterCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    SendToMaintenanceBulkAction::make(),
                    CompleteMaintenanceBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
