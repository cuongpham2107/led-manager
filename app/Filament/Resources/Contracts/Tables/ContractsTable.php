<?php

namespace App\Filament\Resources\Contracts\Tables;

use App\Enums\ContractStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Số HĐ')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Tên dự án')
                    ->placeholder('—')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('contract_value')
                    ->label('Giá trị HĐ')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('deposit_amount')
                    ->label('Tiền cọc')
                    ->money('VND')
                    ->sortable(),
                TextColumn::make('total_paid')
                    ->label('Đã thanh toán')
                    ->money('VND')
                    ->color('success')
                    ->state(fn ($record) => $record->total_paid),
                TextColumn::make('remaining_debt')
                    ->label('Còn nợ')
                    ->money('VND')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->state(fn ($record) => $record->remaining_debt),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable(),
                TextColumn::make('signed_date')
                    ->label('Ngày ký')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(ContractStatus::class),
                SelectFilter::make('customer_id')
                    ->label('Khách hàng')
                    ->relationship('customer', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
