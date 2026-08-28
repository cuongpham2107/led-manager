<?php

namespace App\Filament\Resources\RepairLogs\Tables;

use App\Enums\RepairResultStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RepairLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset.serial_no')
                    ->label('Mã Serial Thiết bị')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('asset.productLine.name')
                    ->label('Dòng LED')
                    ->placeholder('—'),
                TextColumn::make('start_date')
                    ->label('Ngày nhận sửa')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Ngày hoàn thành')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Đang sửa chữa...'),
                TextColumn::make('repair_note')
                    ->label('Mô tả hỏng hóc & linh kiện thay')
                    ->limit(35)
                    ->wrap(),
                TextColumn::make('repair_cost')
                    ->label('Chi phí sửa')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold')
                    ->summarize(
                        Sum::make()
                            ->label('Tổng chi phí sửa')
                            ->money('VND'),
                    ),
                TextColumn::make('result_status')
                    ->label('Kết quả')
                    ->badge()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Kỹ thuật viên')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('result_status')
                    ->label('Kết quả xử lý')
                    ->options(RepairResultStatus::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật phiếu sửa chữa')
                    ->modalDescription('Cập nhật tình trạng khắc phục, kỹ thuật viên phụ trách và chi phí thực tế.')
                    ->modalWidth(Width::FourExtraLarge),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
