<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\CustomerType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã KH')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Tên khách hàng / Doanh nghiệp')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),
                TextColumn::make('type')
                    ->label('Phân loại')
                    ->badge()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('contact_person')
                    ->label('Người liên hệ')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Phân loại khách hàng')
                    ->options(CustomerType::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật thông tin khách hàng')
                    ->modalDescription('Chỉnh sửa thông tin liên hệ, công ty và nhóm khách hàng.')
                    ->modalWidth(Width::FourExtraLarge),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
