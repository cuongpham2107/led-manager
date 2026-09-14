<?php

namespace App\Filament\Resources\Agencies\Tables;

use App\Models\Agency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AgenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã đại lý')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Tên đại lý')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->summarize(Count::make()->label('Tổng số đại lý')),
                TextColumn::make('province')
                    ->label('Tỉnh / TP')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('warehouse.name')
                    ->label('Kho liên kết')
                    ->placeholder('Chưa gán kho')
                    ->searchable(),
                TextColumn::make('allocated_area_m2')
                    ->label('Định mức bàn giao')
                    ->suffix(' m²')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('current_inventory_area')
                    ->label('Tồn kho thực tế')
                    ->state(fn (Agency $record): string => $record->current_inventory_area.' m²')
                    ->badge()
                    ->color(fn (Agency $record): string => $record->current_inventory_area > (float) $record->allocated_area_m2 ? 'danger' : 'success'),
                TextColumn::make('commission_rate')
                    ->label('% Hoa hồng')
                    ->suffix('%')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Nhân sự')
                    ->counts('users')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('orders_count')
                    ->label('Tổng đơn')
                    ->counts('orders')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('province')
                    ->label('Tỉnh thành')
                    ->options(fn () => Agency::query()->whereNotNull('province')->pluck('province', 'province')->toArray()),
                TernaryFilter::make('is_active')
                    ->label('Trạng thái hoạt động'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật thông tin Đại lý')
                    ->modalDescription('Chỉnh sửa thông tin liên hệ, tỷ lệ hoa hồng và định mức diện tích LED.')
                    ->modalWidth(Width::SevenExtraLarge),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
