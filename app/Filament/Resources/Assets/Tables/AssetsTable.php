<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
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
                    ->label('Kho lưu trữ (Warehouse)')
                    ->collapsible(),
                Group::make('productLine.name')
                    ->label('Dòng sản phẩm LED')
                    ->collapsible(),
                Group::make('current_status')
                    ->label('Trạng thái thiết bị')
                    ->collapsible(),
            ])
            ->columns([
                TextColumn::make('serial_no')
                    ->label('SERIAL')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('productLine.name')
                    ->label('PRODUCT LINE')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('size')
                    ->label('SIZE')
                    ->placeholder('0.5×0.5 m')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('manufactured_date')
                    ->label('MANUFACTURED')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('current_status')
                    ->label('STATUS')
                    ->badge()
                    ->sortable(),
                TextColumn::make('currentWarehouse.name')
                    ->label('WAREHOUSE')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('deviceType.name')
                    ->label('TYPE')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchase_cost')
                    ->label('COST')
                    ->money('VND')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('current_status')
                    ->label('Status')
                    ->options(AssetStatus::class),
                SelectFilter::make('product_line_id')
                    ->label('Product Line')
                    ->relationship('productLine', 'name'),
                SelectFilter::make('current_warehouse_id')
                    ->label('Warehouse')
                    ->relationship('currentWarehouse', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('qr_code')
                    ->label('Mã QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
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
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
