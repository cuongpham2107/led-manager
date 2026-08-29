<?php

namespace App\Filament\Resources\CheckinBatches\Tables;

use App\Enums\BatchStatus;
use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CheckinBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã đợt nhập')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('batch_type')
                    ->label('Loại')
                    ->badge()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Kho nhận')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('SL dự kiến')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->placeholder('—'),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('SL items')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->placeholder('0'),
                TextColumn::make('progress')
                    ->label('Tiến độ quét')
                    ->state(function ($record): string {
                        $scanned = $record->items()->where('is_received', true)->count();
                        $target = max((int) $record->quantity, $record->items()->count());

                        if ($target === 0) {
                            return '—';
                        }

                        $percent = (int) round(($scanned / $target) * 100);

                        return "{$scanned}/{$target} ({$percent}%)";
                    })
                    ->badge()
                    ->color(function ($record): string {
                        $scanned = $record->items()->where('is_received', true)->count();
                        $target = max((int) $record->quantity, $record->items()->count());

                        if ($target === 0) {
                            return 'gray';
                        }

                        $percent = (int) round(($scanned / $target) * 100);

                        if ($percent >= 100) {
                            return 'success';
                        }
                        if ($percent > 0) {
                            return 'warning';
                        }

                        return 'gray';
                    })
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Người tạo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expected_date')
                    ->label('Ngày dự kiến')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('completed_at')
                    ->label('Hoàn thành')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(BatchStatus::class),
                SelectFilter::make('batch_type')
                    ->label('Loại nhập')
                    ->options([
                        'production' => 'Sản xuất',
                        'purchase' => 'Mua hàng',
                        'transfer' => 'Chuyển kho',
                    ]),
                SelectFilter::make('warehouse_id')
                    ->label('Kho hàng')
                    ->relationship('warehouse', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modal()
                    ->modalHeading(fn ($record) => "Sửa đợt nhập kho: {$record->code}")
                    ->modalSubmitActionLabel('Lưu thay đổi')
                    ->modalWidth('3xl')
                    ->schema(function ($schema) {
                        // Reuse the resource's form schema so the modal
                        // and the dedicated Edit page stay in lock-step.
                        return CheckinBatchResource::form($schema);
                    }),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
