<?php

namespace App\Filament\Resources\DeviceTypes\Tables;

use App\Enums\DeviceUnit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeviceTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã loại')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Tên chủng loại thiết bị')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('unit')
                    ->label('Đơn vị tính')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                IconColumn::make('requires_serial')
                    ->label('Yêu cầu Serial/QR')
                    ->boolean(),
                TextColumn::make('assets_count')
                    ->label('Số lượng thiết bị')
                    ->counts('assets')
                    ->badge()
                    ->color('info')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('unit')
                    ->label('Đơn vị tính')
                    ->options(DeviceUnit::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật loại thiết bị')
                    ->modalDescription('Chỉnh sửa tên, mã danh mục và quy cách loại thiết bị.')
                    ->modalWidth(Width::ThreeExtraLarge),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
