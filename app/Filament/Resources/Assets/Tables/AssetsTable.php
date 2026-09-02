<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\Actions\CompleteMaintenanceBulkAction;
use App\Filament\Resources\Assets\Actions\SendToMaintenanceBulkAction;
use App\Filament\Resources\Assets\Actions\ViewQrCodeAction;
use App\Models\DeviceType;
use App\Models\ProductLine;
use App\Models\Warehouse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Leek\FilamentRightClick\Menu\ContextMenuItem;
use Leek\FilamentRightClick\Menu\ContextMenuSection;
use Leek\FilamentRightClick\Menu\ContextMenuSeparator;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->groups([
                Group::make('currentWarehouse.name')
                    ->label('Kho lưu trữ')
                    ->collapsible(),
                Group::make('deviceType.name')
                    ->label('Loại thiết bị')
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
                    ->weight('bold')
                    ->columnFilter(ColumnFilter::search())
                    ->summarize(
                        Count::make()
                            ->label('Tổng số lượng'),
                    ),
                TextColumn::make('deviceType.name')
                    ->label('Loại thiết bị')
                    ->searchable()
                    ->sortable()
                    ->columnFilter(
                        ColumnFilter::select()
                            ->syncWith('device_type_id')
                            ->options(fn (): array => DeviceType::query()->pluck('name', 'id')->toArray()),
                    ),
                TextColumn::make('productLine.name')
                    ->label('Dòng sản phẩm')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->columnFilter(
                        ColumnFilter::select()
                            ->syncWith('product_line_id')
                            ->options(fn (): array => ProductLine::query()->pluck('name', 'id')->toArray()),
                    ),
                TextColumn::make('size')
                    ->label('Quy cách / Kích thước')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->columnFilter(ColumnFilter::search()),
                TextColumn::make('current_status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable()
                    ->columnFilter(
                        ColumnFilter::select()
                            ->syncWith('current_status')
                            ->options(fn (): array => collect(AssetStatus::cases())->mapWithKeys(fn (AssetStatus $status): array => [$status->value => $status->getLabel()])->toArray()),
                    ),
                TextColumn::make('currentWarehouse.name')
                    ->label('Kho lưu trữ')
                    ->searchable()
                    ->sortable()
                    ->columnFilter(
                        ColumnFilter::select()
                            ->syncWith('current_warehouse_id')
                            ->options(fn (): array => Warehouse::query()->pluck('name', 'id')->toArray()),
                    ),
                TextColumn::make('purchase_cost')
                    ->label('Nguyên giá')
                    ->money('VND')
                    ->sortable()
                    ->columnFilter(ColumnFilter::range())
                    ->summarize(
                        Sum::make()
                            ->label('Tổng nguyên giá')
                            ->money('VND'),
                    ),
                TextColumn::make('manufactured_date')
                    ->label('Ngày sản xuất')
                    ->date('d/m/Y')
                    ->sortable()
                    ->columnFilter(ColumnFilter::date())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('current_status')
                    ->label('Trạng thái')
                    ->options(fn (): array => collect(AssetStatus::cases())->mapWithKeys(fn (AssetStatus $status): array => [$status->value => $status->getLabel()])->toArray())
                    ->multiple(),
                SelectFilter::make('device_type_id')
                    ->label('Loại thiết bị')
                    ->options(fn (): array => DeviceType::query()->pluck('name', 'id')->toArray())
                    ->preload()
                    ->multiple(),
                SelectFilter::make('product_line_id')
                    ->label('Dòng sản phẩm')
                    ->options(fn (): array => ProductLine::query()->pluck('name', 'id')->toArray())
                    ->preload()
                    ->multiple(),
                SelectFilter::make('current_warehouse_id')
                    ->label('Kho lưu trữ')
                    ->options(fn (): array => Warehouse::query()->pluck('name', 'id')->toArray())
                    ->preload()
                    ->multiple(),
            ])
            ->recordActions([
                ViewQrCodeAction::make(),
                EditAction::make()
                    ->modalHeading('Cập nhật thông tin thiết bị')
                    ->modalDescription('Chỉnh sửa thông số, vị trí kho và trạng thái vận hành của thiết bị.')
                    ->modalWidth(Width::FourExtraLarge),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    SendToMaintenanceBulkAction::make(),
                    CompleteMaintenanceBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->contextMenuActions([
                ContextMenuSection::make([
                    ContextMenuItem::for(ViewQrCodeAction::make())
                        ->label('Xem mã QR')
                        ->icon('heroicon-o-qr-code')
                        ->color('info'),
                    ContextMenuItem::for(
                        EditAction::make()
                            ->modalHeading('Cập nhật thông tin thiết bị')
                            ->modalDescription('Chỉnh sửa thông số, vị trí kho và trạng thái vận hành của thiết bị.')
                            ->modalWidth(Width::FourExtraLarge)
                    )
                        ->label('Chỉnh sửa')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary'),
                ])->label('Thao tác'),
                ContextMenuSeparator::make(),
                ContextMenuItem::for(DeleteAction::make())
                    ->label('Xóa thiết bị')
                    ->icon('heroicon-o-trash')
                    ->color('danger'),
            ])
            ->contextMenuBulkActions([
                ContextMenuItem::forBulkAction(SendToMaintenanceBulkAction::make())
                    ->label('Gửi bảo trì hàng loạt')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('warning'),
                ContextMenuItem::forBulkAction(CompleteMaintenanceBulkAction::make())
                    ->label('Hoàn tất bảo trì hàng loạt')
                    ->icon('heroicon-o-check-circle')
                    ->color('success'),
                ContextMenuSeparator::make(),
                ContextMenuItem::forBulkAction(DeleteBulkAction::make())
                    ->label('Xóa các mục đã chọn')
                    ->icon('heroicon-o-trash')
                    ->color('danger'),
            ]);
    }
}
