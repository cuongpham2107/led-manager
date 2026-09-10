<?php

namespace App\Filament\Resources\CheckinBatches\Tables;

use App\Enums\BatchStatus;
use App\Filament\Resources\CheckinBatches\Actions\ImportCheckinBatchItemsAction;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckinBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->color('primary')
                    ->label('Mã đợt nhập')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                // TextColumn::make('batch_type')
                //     ->label('Loại')
                //     ->badge()
                //     ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Kho nhận')
                    ->searchable()
                    ->sortable(),
                // TextColumn::make('quantity')
                //     ->label('SL dự kiến')
                //     ->numeric()
                //     ->sortable()
                //     ->alignCenter()
                //     ->placeholder('—'),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('SL items')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->placeholder('0'),
                TextColumn::make('progress')
                    ->label('Tiến độ quét')
                    ->state(function (CheckinBatch $record): string {
                        $total = (int) ($record->items_count ?? $record->items()->count());
                        $scanned = (int) ($record->received_items_count ?? $record->items()->where('is_received', true)->count());

                        if ($total > 0) {
                            $percent = min(100, (int) round(($scanned / $total) * 100));

                            return "{$scanned}/{$total} ({$percent}%)";
                        }

                        if ($record->quantity && (int) $record->quantity > 0) {
                            return "0/{$record->quantity} (0%)";
                        }

                        return '—';
                    })
                    ->badge()
                    ->color(function (CheckinBatch $record): string {
                        if ($record->status === BatchStatus::Completed) {
                            return 'success';
                        }

                        $total = (int) ($record->items_count ?? $record->items()->count());
                        $scanned = (int) ($record->received_items_count ?? $record->items()->where('is_received', true)->count());

                        if ($total === 0) {
                            return 'gray';
                        }

                        $percent = min(100, (int) round(($scanned / $total) * 100));

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
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw("CASE checkin_batches.status
                            WHEN 'pending' THEN 1
                            WHEN 'in_progress' THEN 2
                            WHEN 'dispatched' THEN 3
                            WHEN 'completed' THEN 4
                            WHEN 'cancelled' THEN 5
                            ELSE 6
                        END {$direction}");
                    }),
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
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        BatchStatus::Pending->value => BatchStatus::Pending->getLabel(),
                        BatchStatus::InProgress->value => BatchStatus::InProgress->getLabel(),
                        BatchStatus::Completed->value => BatchStatus::Completed->getLabel(),
                        BatchStatus::Cancelled->value => BatchStatus::Cancelled->getLabel(),
                    ]),
                SelectFilter::make('batch_type')
                    ->label('Loại nhập')
                    ->options([
                        'production' => 'Sản xuất',
                        'purchase' => 'Mua hàng',
                        'transfer' => 'Chuyển kho',
                    ]),
                SelectFilter::make('warehouse_id')
                    ->label('Kho hàng')
                    ->relationship('warehouse', 'name')
                    ->hidden(fn (): bool => (bool) auth()->user()?->getScopedWarehouseId()),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                ImportCheckinBatchItemsAction::make(),
                EditAction::make()
                    ->label('Chỉnh sửa')
                    ->modalHeading('Sửa đợt nhập')
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalSubmitActionLabel('Lưu')
                    ->modalCancelActionLabel('Hủy')
                    ->extraModalFooterActions(fn (CheckinBatch $record): array => [
                        ImportCheckinBatchItemsAction::make('modal_import_sheet')
                            ->cancelParentActions(),
                        Action::make('completeReceiving')
                            ->label('Kết thúc nhận hàng')
                            ->color('gray')
                            ->icon('heroicon-o-check-circle')
                            ->visible(fn (): bool => $record->status !== BatchStatus::Completed)
                            ->requiresConfirmation()
                            ->modalHeading('Kết thúc nhận hàng')
                            ->modalDescription("Bạn có chắc chắn muốn kết thúc nhận hàng cho đợt nhập {$record->code} không? Tất cả các thiết bị chưa nhận sẽ được đánh dấu nhận hàng hoàn tất và cập nhật trạng thái về kho.")
                            ->modalSubmitActionLabel('Xác nhận kết thúc')
                            ->modalCancelActionLabel('Hủy')
                            ->action(function (CheckinBatch $record) {
                                $record->complete(Auth::user());

                                Notification::make()
                                    ->title("Đợt nhập kho {$record->code} đã kết thúc nhận hàng hoàn tất!")
                                    ->success()
                                    ->send();
                            })
                            ->cancelParentActions(),
                    ])
                    ->mutateRecordDataUsing(function (array $data, CheckinBatch $record): array {
                        $data['selected_assets'] = $record->items()->pluck('asset_id')->map(fn ($id) => (int) $id)->toArray();

                        return $data;
                    })
                    ->using(function (CheckinBatch $record, array $data): CheckinBatch {
                        return DB::transaction(function () use ($record, $data) {
                            $selectedAssets = $data['selected_assets'] ?? [];
                            unset($data['selected_assets']);

                            $record->update([
                                'code' => $data['code'],
                                'note' => $data['note'] ?? null,
                                'warehouse_id' => $data['warehouse_id'],
                                'expected_date' => $data['expected_date'] ?? null,
                            ]);

                            $selectedIds = collect($selectedAssets)->map(fn ($id) => (int) $id)->filter()->values();
                            $currentIds = $record->items()->pluck('asset_id')->map(fn ($id) => (int) $id);

                            $toDelete = $currentIds->diff($selectedIds);
                            $toAdd = $selectedIds->diff($currentIds);

                            if ($toDelete->isNotEmpty()) {
                                $record->items()->whereIn('asset_id', $toDelete)->delete();
                            }

                            foreach ($toAdd as $assetId) {
                                CheckinBatchItem::create([
                                    'checkin_batch_id' => $record->id,
                                    'asset_id' => $assetId,
                                    'condition' => 'ok',
                                    'is_received' => true,
                                    'received_at' => now(),
                                    'received_by' => Auth::id(),
                                ]);
                            }

                            if ($selectedIds->isNotEmpty()) {
                                Asset::whereIn('id', $selectedIds)->update([
                                    'current_warehouse_id' => $data['warehouse_id'],
                                ]);
                            }

                            return $record;
                        });
                    }),
                DeleteAction::make()
                    ->label(''),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('status', 'asc');
    }
}
