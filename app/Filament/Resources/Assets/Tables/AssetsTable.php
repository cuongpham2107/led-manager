<?php

namespace App\Filament\Resources\Assets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serial_no')
                    ->label('Serial No')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('deviceType.name')
                    ->label('Loại thiết bị')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('productLine.name')
                    ->label('Dòng sản phẩm')
                    ->placeholder('N/A')
                    ->searchable(),
                TextColumn::make('current_status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ready' => 'success',
                        'in_event' => 'info',
                        'in_transit' => 'warning',
                        'repairing' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ready' => 'Sẵn sàng',
                        'in_event' => 'Đang sự kiện',
                        'in_transit' => 'Vận chuyển',
                        'repairing' => 'Bảo dưỡng',
                        'disposed' => 'Thanh lý',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('currentWarehouse.name')
                    ->label('Kho hiện tại')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Quy cách')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchase_cost')
                    ->label('Nguyên giá')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' đ')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
