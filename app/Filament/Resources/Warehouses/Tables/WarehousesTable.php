<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã kho')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Tên kho hàng')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->summarize(
                        Count::make()
                            ->label('Tổng số kho'),
                    ),
                TextColumn::make('address')
                    ->label('Địa chỉ kho')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->searchable(),
                TextColumn::make('assets_count')
                    ->label('Tổng thiết bị')
                    ->counts('assets')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật thông tin kho hàng')
                    ->modalDescription('Chỉnh sửa tên, địa chỉ, người quản lý và số điện thoại liên hệ của kho.')
                    ->modalWidth(Width::FourExtraLarge),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
