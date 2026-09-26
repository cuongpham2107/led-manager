<?php

namespace App\Filament\Resources\LedConfigurations\Tables;

use App\Enums\AssetStatus;
use App\Enums\LedScanMode;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LedConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with('productLine')
                ->withCount(['assets', 'assets as ready_assets_count' => fn ($q) => $q->where('current_status', AssetStatus::Ready)]))
            ->columns([
                TextColumn::make('productLine.name')
                    ->label('Dòng LED')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Tên cấu hình')
                    ->searchable(),
                TextColumn::make('receiving_card')
                    ->label('Card nhận')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('scan_mode')
                    ->label('Kiểu quét')
                    ->badge(),
                TextColumn::make('controller_model')
                    ->label('Đầu phát')
                    ->searchable()
                    ->badge()
                    ->color('warning'),
                TextColumn::make('assets_count')
                    ->label('Tổng tấm')
                    ->sortable(),
                TextColumn::make('ready_assets_count')
                    ->label('Sẵn sàng')
                    ->color('success')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Sử dụng')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('product_line_id')
                    ->label('Dòng sản phẩm')
                    ->relationship('productLine', 'name'),
                SelectFilter::make('scan_mode')
                    ->label('Kiểu quét')
                    ->options(LedScanMode::class),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật cấu hình LED')
                    ->modalWidth(Width::ThreeExtraLarge),
            ], position: RecordActionsPosition::BeforeCells);
    }
}
