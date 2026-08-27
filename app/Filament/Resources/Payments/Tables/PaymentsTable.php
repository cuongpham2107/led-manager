<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã phiếu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contract.code')
                    ->label('Số HĐ')
                    ->placeholder('—')
                    ->searchable()
                    ->badge(),
                TextColumn::make('type')
                    ->label('Loại')
                    ->badge(),
                TextColumn::make('method')
                    ->label('Hình thức')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Số tiền')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('payment_date')
                    ->label('Ngày thu')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Mã GD / HĐ')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('receiver.name')
                    ->label('Người thu')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Loại thu')
                    ->options(PaymentType::class),
                SelectFilter::make('method')
                    ->label('Hình thức')
                    ->options(PaymentMethod::class),
                SelectFilter::make('customer_id')
                    ->label('Khách hàng')
                    ->relationship('customer', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật phiếu thu / thanh toán')
                    ->modalDescription('Chỉnh sửa thông tin số tiền, hình thức thanh toán và ghi chú.')
                    ->modalWidth(Width::FourExtraLarge),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
