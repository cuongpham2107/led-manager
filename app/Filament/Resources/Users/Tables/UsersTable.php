<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Packstub\AccountSwitcher\Filament\Actions\ImpersonateAction;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->summarize(
                        Count::make()
                            ->label('Tổng tài khoản'),
                    ),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Điện thoại')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('roles.name')
                    ->label('Vai trò')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('warehouse.name')
                    ->label('Kho công tác')
                    ->placeholder('Toàn hệ thống'),
                IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Kho công tác')
                    ->relationship('warehouse', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật tài khoản người dùng')
                    ->modalDescription('Chỉnh sửa thông tin cá nhân, phân quyền vai trò và trạng thái hoạt động.')
                    ->modalWidth(Width::ThreeExtraLarge),
                ImpersonateAction::make(),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
