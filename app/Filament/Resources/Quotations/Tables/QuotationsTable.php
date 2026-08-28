<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\Actions\ConvertToOrderAction;
use App\Filament\Resources\Quotations\Actions\DownloadPdfAction;
use App\Filament\Resources\Quotations\Actions\MarkRejectedAction;
use App\Models\Quotation;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
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
                    ->weight('bold'),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('event_name')
                    ->label('Sự kiện')
                    ->searchable()
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('productLine.name')
                    ->label('Dòng LED')
                    ->badge()
                    ->color('primary')
                    ->placeholder('N/A')
                    ->sortable(),
                TextColumn::make('screen_area_m2')
                    ->label('Diện tích')
                    ->suffix(' m²')
                    ->numeric(2)
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Tổng diện tích')
                            ->suffix(' m²')
                            ->numeric(2),
                    ),
                TextColumn::make('rental_days')
                    ->label('Số ngày')
                    ->suffix(' ngày')
                    ->sortable(),
                TextColumn::make('total_price')
                    ->label('Tổng giá trị')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold')
                    ->summarize(
                        Sum::make()
                            ->label('Tổng tiền báo giá')
                            ->money('VND'),
                    ),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable(),
                TextColumn::make('salesUser.name')
                    ->label('Sales')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(QuotationStatus::class),
                SelectFilter::make('customer_id')
                    ->label('Khách hàng')
                    ->relationship('customer', 'name'),
                SelectFilter::make('product_line_id')
                    ->label('Dòng sản phẩm')
                    ->relationship('productLine', 'name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn (Quotation $record): bool => in_array($record->status, [
                            QuotationStatus::Draft,
                            QuotationStatus::Sent,
                            QuotationStatus::Approved,
                        ])),
                    ConvertToOrderAction::make(),
                    DownloadPdfAction::make(),
                    MarkRejectedAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
