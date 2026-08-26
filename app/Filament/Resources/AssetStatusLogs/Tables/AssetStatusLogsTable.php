<?php

namespace App\Filament\Resources\AssetStatusLogs\Tables;

use App\Enums\AssetStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
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
                SelectFilter::make('to_status')
                    ->label('Trạng thái mới')
                    ->options(AssetStatus::class),
            ])
            ->recordActions([
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
