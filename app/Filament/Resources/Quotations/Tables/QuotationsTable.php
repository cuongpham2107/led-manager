<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\Actions\ConvertToOrderAction;
use App\Filament\Resources\Quotations\Actions\DownloadPdfAction;
use App\Filament\Resources\Quotations\Actions\MarkRejectedAction;
use App\Filament\Resources\Quotations\Actions\ViewOrderAction;
use App\Models\Quotation;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã Báo Giá')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable(),
                TextColumn::make('customer.company_name')
                    ->label('Khách Hàng')
                    ->searchable()
                    ->sortable()
                    ->limit(25),
                TextColumn::make('event_name')
                    ->label('Sự Kiện')
                    ->searchable()
                    ->limit(25)
                    ->toggleable(),
                TextColumn::make('productLine.name')
                    ->label('Dòng LED')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('screen_area_m2')
                    ->label('Diện Tích')
                    ->suffix(' m²')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('rental_days')
                    ->label('Ngày Thuê')
                    ->suffix(' ngày')
                    ->sortable(),
                TextColumn::make('event_start_date')
                    ->label('Ngày Bắt Đầu')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('event_end_date')
                    ->label('Ngày Kết Thúc')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_cost')
                    ->label('Dự Toán Chi Phí')
                    ->money('VND')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_price')
                    ->label('Tổng Giá')
                    ->money('VND')
                    ->sortable()
                    ->summarize(Sum::make()->money('VND')->label('Tổng Doanh Thu')),
                TextColumn::make('margin_percent')
                    ->label('Margin')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 50 => 'success',
                        $state >= 30 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('status')
                    ->label('Trạng Thái')
                    ->badge()
                    ->sortable(),
                TextColumn::make('salesUser.name')
                    ->label('Phụ Trách')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Ngày Tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(QuotationStatus::class),
                SelectFilter::make('product_line_id')
                    ->label('Dòng LED')
                    ->relationship('productLine', 'name'),
                SelectFilter::make('sales_user_id')
                    ->label('Nhân viên sales')
                    ->relationship('salesUser', 'name'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Quotation $record): bool => in_array($record->status, [
                        QuotationStatus::Draft,
                        QuotationStatus::Sent,
                        QuotationStatus::Approved,
                    ])),
                ActionGroup::make([
                    ConvertToOrderAction::make(),
                    ViewOrderAction::make(),
                    DownloadPdfAction::make(),
                    MarkRejectedAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('status', 'asc');
    }
}
