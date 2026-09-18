<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\CustomerType;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
                    ->color('primary')
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Tên khách hàng / Doanh nghiệp')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap()
                    ->summarize(
                        Count::make()
                            ->label('Tổng khách hàng'),
                    ),
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
                TextColumn::make('agency.name')
                    ->label('Đại lý')
                    ->placeholder('Tổng công ty (HQ)')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->hidden(function (): bool {
                        $user = Auth::user();

                        return (bool) ($user instanceof User && $user->isAgencyScoped());
                    }),
                IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Phân loại khách hàng')
                    ->options(CustomerType::class),
                SelectFilter::make('agency_id')
                    ->label('Đại lý quản lý')
                    ->relationship('agency', 'name')
                    ->placeholder('Tất cả đại lý')
                    ->hidden(function (): bool {
                        $user = Auth::user();

                        return (bool) ($user instanceof User && $user->isAgencyScoped());
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật thông tin khách hàng')
                    ->modalDescription('Chỉnh sửa thông tin liên hệ, công ty và nhóm khách hàng.')
                    ->modalWidth(Width::FourExtraLarge),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
