<?php

namespace App\Filament\Resources\CheckoutBatches\Tables;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Filament\Resources\CheckoutBatches\Actions\CreateReturnBatchAction;
use App\Filament\Resources\CheckoutBatches\Actions\ViewReturnBatchAction;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['order', 'customer', 'warehouse', 'returnBatches']))
            ->columns([
                TextColumn::make('code')
                    ->color('primary')
                    ->label('Mã đợt xuất')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('order.order_no')
                    ->label('Đơn hàng')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Kho xuất')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('required_area_m2')
                    ->label('Diện tích')
                    ->suffix(' m²')
                    ->numeric(2)
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Tổng diện tích')
                            ->suffix(' m²')
                            ->numeric(2),
                    ),
                TextColumn::make('expected_return_date')
                    ->label('Dự kiến trả')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw("CASE checkout_batches.status
                            WHEN 'pending' THEN 1
                            WHEN 'in_progress' THEN 2
                            WHEN 'dispatched' THEN 3
                            WHEN 'completed' THEN 4
                            WHEN 'cancelled' THEN 5
                            ELSE 6
                        END {$direction}");
                    }),
                TextColumn::make('dispatched_at')
                    ->label('Thời gian xuất')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(BatchStatus::class),
                SelectFilter::make('warehouse_id')
                    ->label('Kho hàng')
                    ->relationship('warehouse', 'name')
                    ->hidden(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return (bool) $user?->getScopedWarehouseId();
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                EditAction::make()
                    ->label('Chỉnh sửa')
                    ->modalHeading(fn (CheckoutBatch $record): string => "Sửa đợt xuất: {$record->code}")
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalSubmitActionLabel('Lưu đợt xuất')
                    ->modalCancelActionLabel('Đóng')
                    ->mutateRecordDataUsing(function (array $data, CheckoutBatch $record): array {
                        $data['selected_assets'] = $record->items()->pluck('asset_id')->map(fn ($id) => (int) $id)->toArray();

                        return $data;
                    })
                    ->using(function (CheckoutBatch $record, array $data): CheckoutBatch {
                        return DB::transaction(function () use ($record, $data) {
                            $selectedAssets = $data['selected_assets'] ?? [];
                            unset($data['selected_assets']);
                            unset($data['product_line_id']);

                            $record->update($data);

                            $newAssetIds = collect($selectedAssets)->map(fn ($id) => (int) $id)->filter()->values();
                            $currentAssetIds = $record->items()->pluck('asset_id')->map(fn ($id) => (int) $id);

                            $toRemove = $currentAssetIds->diff($newAssetIds);
                            $toAdd = $newAssetIds->diff($currentAssetIds);

                            // Remove unselected assets
                            if ($toRemove->isNotEmpty()) {
                                $record->items()->whereIn('asset_id', $toRemove)->delete();

                                Asset::whereIn('id', $toRemove)->update(['current_status' => AssetStatus::Ready]);

                                foreach ($toRemove as $removedId) {
                                    AssetStatusLog::create([
                                        'asset_id' => $removedId,
                                        'from_status' => AssetStatus::InTransit,
                                        'to_status' => AssetStatus::Ready,
                                        'from_warehouse_id' => $record->warehouse_id,
                                        'to_warehouse_id' => $record->warehouse_id,
                                        'source_type' => CheckoutBatch::class,
                                        'source_id' => $record->id,
                                        'changed_by' => Auth::id(),
                                        'note' => "Gỡ khỏi đợt xuất: Đợt {$record->code}",
                                        'created_at' => now(),
                                    ]);
                                }
                            }

                            // Add newly selected assets
                            foreach ($toAdd as $addId) {
                                CheckoutBatchItem::create([
                                    'checkout_batch_id' => $record->id,
                                    'asset_id' => $addId,
                                    'is_dispatched' => true,
                                    'dispatched_by' => Auth::id(),
                                    'dispatched_at' => now(),
                                    'note' => $record->note,
                                ]);

                                $asset = Asset::find($addId);
                                if ($asset) {
                                    $oldStatus = $asset->current_status;
                                    $asset->update(['current_status' => AssetStatus::InTransit]);

                                    AssetStatusLog::create([
                                        'asset_id' => $asset->id,
                                        'from_status' => $oldStatus,
                                        'to_status' => AssetStatus::InTransit,
                                        'from_warehouse_id' => $asset->current_warehouse_id,
                                        'to_warehouse_id' => $record->warehouse_id,
                                        'source_type' => CheckoutBatch::class,
                                        'source_id' => $record->id,
                                        'changed_by' => Auth::id(),
                                        'note' => "Bổ sung vào đợt xuất: Đợt {$record->code}",
                                        'created_at' => now(),
                                    ]);
                                }
                            }

                            if ($newAssetIds->isNotEmpty() && $record->status === BatchStatus::Pending) {
                                $record->update(['status' => BatchStatus::InProgress]);
                            } elseif ($newAssetIds->isEmpty() && $record->status === BatchStatus::InProgress) {
                                $record->update(['status' => BatchStatus::Pending]);
                            }

                            return $record;
                        });
                    }),
                ActionGroup::make([
                    CreateReturnBatchAction::make(),
                    ViewReturnBatchAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('status', 'asc');
    }
}
