<?php

namespace App\Filament\Resources\PricingRules\Tables;

use App\Enums\CustomerType;
use App\Models\PricingRule;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PricingRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tên đợt giá')
                    ->searchable()
                    ->placeholder('Tiêu chuẩn')
                    ->weight('bold'),
                TextColumn::make('productLine.name')
                    ->label('Dòng máy LED')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('agency.name')
                    ->label('Đại lý')
                    ->placeholder('Toàn quốc')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('base_price_per_unit_per_day')
                    ->label('Đơn giá máy / ngày')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('effective_period')
                    ->label('Thời điểm hiệu lực')
                    ->state(function (PricingRule $record): string {
                        $from = $record->effective_from ? Carbon::parse($record->effective_from)->format('d/m/Y') : 'Từ trước';
                        $to = $record->effective_to ? Carbon::parse($record->effective_to)->format('d/m/Y') : 'Vô thời hạn';

                        return "{$from} – {$to}";
                    })
                    ->badge()
                    ->color(function (PricingRule $record): string {
                        $now = now()->toDateString();
                        $fromStr = $record->effective_from ? Carbon::parse($record->effective_from)->toDateString() : null;
                        $toStr = $record->effective_to ? Carbon::parse($record->effective_to)->toDateString() : null;

                        if ($fromStr && $fromStr > $now) {
                            return 'gray'; // Chưa tới hạn
                        }
                        if ($toStr && $toStr < $now) {
                            return 'danger'; // Hết hạn
                        }

                        return 'success'; // Đang hiệu lực
                    }),
                TextColumn::make('day_range')
                    ->label('Khung ngày')
                    ->state(fn ($record) => $record->max_days ? "{$record->min_days} – {$record->max_days} ngày" : "Từ {$record->min_days} ngày")
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discount_percent')
                    ->label('Chiết khấu')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Kích hoạt')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product_line_id')
                    ->label('Dòng LED')
                    ->relationship('productLine', 'name'),
                SelectFilter::make('agency_id')
                    ->label('Đại lý')
                    ->relationship('agency', 'name')
                    ->placeholder('Tất cả đại lý'),
                SelectFilter::make('customer_type')
                    ->label('Nhóm khách')
                    ->options(CustomerType::class),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật bảng giá thuê máy/ngày')
                    ->modalDescription('Chỉnh sửa đơn giá, thời điểm hiệu lực và đại lý áp dụng.')
                    ->modalWidth(Width::FiveExtraLarge),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
