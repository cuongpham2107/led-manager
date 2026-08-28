<?php

namespace App\Filament\Resources\PricingRules\Tables;

use App\Enums\CustomerType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PricingRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('productLine.name')
                    ->label('Dòng LED')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer_type')
                    ->label('Nhóm khách hàng')
                    ->badge()
                    ->placeholder('Tất cả'),
                TextColumn::make('base_price_per_unit_per_day')
                    ->label('Giá / tấm / ngày')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('day_range')
                    ->label('Khung ngày thuê')
                    ->state(fn ($record) => $record->max_days ? "{$record->min_days} – {$record->max_days} ngày" : "Từ {$record->min_days} ngày trở lên")
                    ->badge(),
                TextColumn::make('discount_percent')
                    ->label('Chiết khấu')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('crew_rate_per_person_per_day')
                    ->label('Nhân công / ngày')
                    ->money('VND')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transport_rate_per_km')
                    ->label('Vận chuyển / km')
                    ->money('VND')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('accessory_rate_per_m2')
                    ->label('Phụ kiện / m²')
                    ->money('VND')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Kích hoạt')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product_line_id')
                    ->label('Dòng LED')
                    ->relationship('productLine', 'name'),
                SelectFilter::make('customer_type')
                    ->label('Nhóm khách')
                    ->options(CustomerType::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật bảng giá thuê')
                    ->modalDescription('Chỉnh sửa đơn giá, chiết khấu và chi phí nhân công/vận chuyển.')
                    ->modalWidth(Width::FiveExtraLarge),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
