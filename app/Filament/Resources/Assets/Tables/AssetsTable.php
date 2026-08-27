<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup('currentWarehouse.name')
            ->groups([
                Group::make('currentWarehouse.name')
                    ->label('Kho lưu trữ')
                    ->collapsible(),
                Group::make('productLine.name')
                    ->label('Dòng sản phẩm')
                    ->collapsible(),
                Group::make('current_status')
                    ->label('Trạng thái thiết bị')
                    ->collapsible(),
            ])
            ->columns([
                TextColumn::make('serial_no')
                    ->label('Số Serial')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('productLine.name')
                    ->label('Dòng sản phẩm')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Kích thước')
                    ->placeholder('0.5×0.5 m')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('manufactured_date')
                    ->label('Ngày sản xuất')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('current_status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable(),
                TextColumn::make('currentWarehouse.name')
                    ->label('Kho lưu trữ')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('deviceType.name')
                    ->label('Loại thiết bị')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchase_cost')
                    ->label('Nguyên giá')
                    ->money('VND')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('current_status')
                    ->label('Trạng thái')
                    ->options(AssetStatus::class),
                SelectFilter::make('product_line_id')
                    ->label('Dòng sản phẩm')
                    ->relationship('productLine', 'name'),
                SelectFilter::make('current_warehouse_id')
                    ->label('Kho lưu trữ')
                    ->relationship('currentWarehouse', 'name'),
            ])
            ->recordActions([
                Action::make('qr_code')
                    ->label('Mã QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->button()
                    ->modalHeading(fn (Asset $record): string => "Mã QR Thiết bị: {$record->serial_no}")
                    ->modalContent(fn (Asset $record) => view('filament.components.asset-qr-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
                EditAction::make()
                    ->modalHeading('Cập nhật thông tin thiết bị')
                    ->modalDescription('Chỉnh sửa thông số, vị trí kho và trạng thái vận hành của thiết bị.')
                    ->modalWidth(Width::FourExtraLarge),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
