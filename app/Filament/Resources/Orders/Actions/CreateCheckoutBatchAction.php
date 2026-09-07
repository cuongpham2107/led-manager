<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\Order;
use App\Services\CodeGeneratorService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CreateCheckoutBatchAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'create_checkout_batch';
    }

    public static function resolveRequiredQuantity(Order $record): int
    {
        // 1. Tổng số lượng từ các mục chi tiết trong đơn hàng (Order Items)
        $itemsSum = (int) $record->items()->sum('quantity_required');
        if ($itemsSum > 0) {
            return $itemsSum;
        }

        // 2. Nhu cầu từ báo giá liên kết (Quotation)
        if ($record->quotation) {
            if ($record->quotation->estimated_cabinet_qty > 0) {
                return (int) $record->quotation->estimated_cabinet_qty;
            }

            $quoItemsSum = (int) $record->quotation->items()->sum('quantity');
            if ($quoItemsSum > 0) {
                return $quoItemsSum;
            }
        }

        // 3. Tính toán từ diện tích màn hình area_m2
        if ((float) $record->area_m2 > 0) {
            $pl = $record->productLine;
            $cabArea = ($pl && (float) $pl->module_width_mm > 0 && (float) $pl->module_height_mm > 0)
                ? ((float) $pl->module_width_mm / 1000) * ((float) $pl->module_height_mm / 1000)
                : 0.5;

            if ($cabArea > 0) {
                return max(1, (int) round((float) $record->area_m2 / $cabArea));
            }
        }

        return 36;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('CreateCheckoutBatch:Order')
            ->label('Tạo Đợt Xuất Kho')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->modalHeading('Tạo Đợt Xuất Kho')
            ->modalDescription('Khởi tạo phiếu xuất kho cho đơn hàng và tuỳ chọn tự động phân bổ thiết bị sẵn sàng từ kho.')
            ->modalSubmitActionLabel('Xác nhận & Tạo đợt xuất')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Draft && ! $record->checkoutBatches()->exists())
            ->form([
                Placeholder::make('order_summary')
                    ->label('Thông tin đơn hàng & Kho xuất')
                    ->content(function (Order $record): HtmlString {
                        $requiredQty = static::resolveRequiredQuantity($record);
                        $warehouseName = e($record->warehouse?->name ?? 'Chưa xác định');
                        $plName = e($record->productLine?->name ?? 'Theo danh mục đơn hàng');

                        $readyCount = Asset::query()
                            ->where('current_warehouse_id', $record->warehouse_id)
                            ->where('current_status', AssetStatus::Ready)
                            ->whereDoesntHave('checkoutBatchItems', function ($q) {
                                $q->whereHas('checkoutBatch', function ($b) {
                                    $b->whereNotIn('status', [BatchStatus::Completed, BatchStatus::Cancelled]);
                                });
                            })
                            ->count();

                        $stockBadgeClass = $readyCount >= $requiredQty
                            ? 'text-emerald-600 dark:text-emerald-400 font-semibold'
                            : 'text-amber-600 dark:text-amber-400 font-semibold';

                        return new HtmlString("
                            <div class=\"rounded-lg border border-gray-200 bg-gray-50/50 p-3.5 text-sm dark:border-gray-700 dark:bg-gray-800/50 space-y-1.5\">
                                <div class=\"flex justify-between\">
                                    <span class=\"text-gray-500 dark:text-gray-400\">Đơn hàng:</span>
                                    <span class=\"font-medium text-gray-900 dark:text-gray-100\">{$record->order_no}</span>
                                </div>
                                <div class=\"flex justify-between\">
                                    <span class=\"text-gray-500 dark:text-gray-400\">Kho xuất hàng:</span>
                                    <span class=\"font-medium text-gray-900 dark:text-gray-100\">{$warehouseName}</span>
                                </div>
                                <div class=\"flex justify-between\">
                                    <span class=\"text-gray-500 dark:text-gray-400\">Dòng sản phẩm:</span>
                                    <span class=\"font-medium text-gray-900 dark:text-gray-100\">{$plName}</span>
                                </div>
                                <div class=\"flex justify-between border-t border-gray-200/80 pt-1.5 dark:border-gray-700/80\">
                                    <span class=\"text-gray-500 dark:text-gray-400\">Nhu cầu xuất:</span>
                                    <span class=\"font-bold text-primary-600 dark:text-primary-400\">{$requiredQty} thiết bị</span>
                                </div>
                                <div class=\"flex justify-between\">
                                    <span class=\"text-gray-500 dark:text-gray-400\">Tồn kho sẵn sàng:</span>
                                    <span class=\"{$stockBadgeClass}\">{$readyCount} thiết bị khả dụng</span>
                                </div>
                            </div>
                        ");
                    }),

                Toggle::make('auto_assign_assets')
                    ->label('Tự động chọn & gán thiết bị từ kho')
                    ->default(true)
                    ->live()
                    ->helperText('Hệ thống tự động tìm và gán các thiết bị sẵn sàng trong kho theo đúng nhu cầu đơn hàng.'),

                TextInput::make('quantity')
                    ->label('Số lượng thiết bị cần gán')
                    ->default(fn (Order $record): int => static::resolveRequiredQuantity($record))
                    ->numeric()
                    ->minValue(1)
                    ->step(1)
                    ->required(fn (Get $get): bool => (bool) $get('auto_assign_assets'))
                    ->visible(fn (Get $get): bool => (bool) $get('auto_assign_assets'))
                    ->helperText(fn (Order $record): string => 'Đề xuất theo định mức/diện tích đơn hàng: '.static::resolveRequiredQuantity($record).' thiết bị.'),

                Toggle::make('mark_dispatched')
                    ->label('Đánh dấu sẵn sàng xuất (Scan to Dispatch)')
                    ->default(true)
                    ->visible(fn (Get $get): bool => (bool) $get('auto_assign_assets'))
                    ->helperText('Đánh dấu thiết bị đã kiểm đếm (is_dispatched = true) và chuyển sang trạng thái Đang vận chuyển để có thể Xuất kho đi sự kiện ngay.'),
            ])
            ->action(function (Order $record, array $data): void {
                $code = CodeGeneratorService::generate('OUT', 'checkout_batches');
                $autoAssign = (bool) ($data['auto_assign_assets'] ?? true);
                $targetQty = (int) ($data['quantity'] ?? static::resolveRequiredQuantity($record));
                $markDispatched = (bool) ($data['mark_dispatched'] ?? true);

                /** @var Collection<int, array{asset: Asset, note: string}> $assignedAssets */
                $assignedAssets = collect();

                if ($autoAssign && $targetQty > 0) {
                    $alreadyPickedIds = [];
                    $orderItems = $record->items;

                    // Phân bổ theo từng order item nếu có
                    if ($orderItems->isNotEmpty()) {
                        foreach ($orderItems as $item) {
                            $needed = (int) $item->quantity_required;
                            if ($needed <= 0) {
                                continue;
                            }

                            $remainingAllowed = $targetQty - count($alreadyPickedIds);
                            if ($remainingAllowed <= 0) {
                                break;
                            }

                            $takeCount = min($needed, $remainingAllowed);

                            $matchedAssets = Asset::query()
                                ->where('current_warehouse_id', $record->warehouse_id)
                                ->where('current_status', AssetStatus::Ready)
                                ->when($item->product_line_id, fn ($q, $plId) => $q->where('product_line_id', $plId))
                                ->when(! empty($alreadyPickedIds), fn ($q) => $q->whereNotIn('id', $alreadyPickedIds))
                                ->whereDoesntHave('checkoutBatchItems', function ($q) {
                                    $q->whereHas('checkoutBatch', function ($b) {
                                        $b->whereNotIn('status', [BatchStatus::Completed, BatchStatus::Cancelled]);
                                    });
                                })
                                ->take($takeCount)
                                ->get();

                            foreach ($matchedAssets as $asset) {
                                $alreadyPickedIds[] = $asset->id;
                                $assignedAssets->push([
                                    'asset' => $asset,
                                    'note' => $item->note ?? "Gán theo định mức đơn hàng {$record->order_no}",
                                ]);
                            }
                        }
                    }

                    // Bổ sung các thiết bị còn thiếu nếu chưa đủ targetQty
                    $remainingNeeded = $targetQty - count($alreadyPickedIds);
                    if ($remainingNeeded > 0) {
                        $fillAssetsQuery = Asset::query()
                            ->where('current_warehouse_id', $record->warehouse_id)
                            ->where('current_status', AssetStatus::Ready)
                            ->when(! empty($alreadyPickedIds), fn ($q) => $q->whereNotIn('id', $alreadyPickedIds))
                            ->whereDoesntHave('checkoutBatchItems', function ($q) {
                                $q->whereHas('checkoutBatch', function ($b) {
                                    $b->whereNotIn('status', [BatchStatus::Completed, BatchStatus::Cancelled]);
                                });
                            });

                        if ($record->product_line_id) {
                            $fillAssetsQuery->orderByRaw('CASE WHEN product_line_id = ? THEN 0 ELSE 1 END', [$record->product_line_id]);
                        }

                        $fillAssets = $fillAssetsQuery->take($remainingNeeded)->get();

                        foreach ($fillAssets as $asset) {
                            $alreadyPickedIds[] = $asset->id;
                            $assignedAssets->push([
                                'asset' => $asset,
                                'note' => "Tự động gán xuất kho cho đơn hàng {$record->order_no}",
                            ]);
                        }
                    }
                }

                DB::transaction(function () use ($record, $code, $autoAssign, $targetQty, $markDispatched, $assignedAssets): void {
                    $batch = CheckoutBatch::create([
                        'code' => $code,
                        'order_id' => $record->id,
                        'customer_id' => $record->customer_id,
                        'warehouse_id' => $record->warehouse_id,
                        'required_area_m2' => $record->area_m2,
                        'expected_return_date' => $record->expected_return_date,
                        'status' => ($autoAssign && $assignedAssets->isNotEmpty() && $markDispatched)
                            ? BatchStatus::InProgress
                            : BatchStatus::Pending,
                        'created_by' => Auth::id(),
                    ]);

                    if ($autoAssign && $assignedAssets->isNotEmpty()) {
                        foreach ($assignedAssets as $entry) {
                            /** @var Asset $asset */
                            $asset = $entry['asset'];
                            $note = $entry['note'];

                            CheckoutBatchItem::create([
                                'checkout_batch_id' => $batch->id,
                                'asset_id' => $asset->id,
                                'is_dispatched' => $markDispatched,
                                'dispatched_by' => $markDispatched ? Auth::id() : null,
                                'dispatched_at' => $markDispatched ? now() : null,
                                'note' => $note,
                            ]);

                            if ($markDispatched) {
                                $oldStatus = $asset->current_status;
                                $asset->update(['current_status' => AssetStatus::InTransit]);

                                AssetStatusLog::create([
                                    'asset_id' => $asset->id,
                                    'from_status' => $oldStatus,
                                    'to_status' => AssetStatus::InTransit,
                                    'from_warehouse_id' => $asset->current_warehouse_id,
                                    'to_warehouse_id' => $batch->warehouse_id,
                                    'source_type' => CheckoutBatch::class,
                                    'source_id' => $batch->id,
                                    'changed_by' => Auth::id(),
                                    'note' => "Tự động gán & chuẩn bị xuất kho: Đợt {$code} (Đơn hàng {$record->order_no})",
                                    'created_at' => now(),
                                ]);
                            }
                        }
                    }

                    $record->update([
                        'status' => OrderStatus::OutboundCreated,
                    ]);

                    $record->refresh();

                    $notification = Notification::make();
                    if ($autoAssign) {
                        $assignedCount = $assignedAssets->count();
                        if ($assignedCount >= $targetQty) {
                            $notification->title('Đã tạo phiếu xuất kho & gán thiết bị!')
                                ->body("Phiếu xuất kho {$code} đã được tạo thành công và tự động gán đủ {$assignedCount} thiết bị từ kho.")
                                ->success();
                        } elseif ($assignedCount > 0) {
                            $notification->title('Đã tạo phiếu xuất kho (Thiếu một số thiết bị)!')
                                ->body("Phiếu xuất kho {$code} đã được tạo và gán {$assignedCount}/{$targetQty} thiết bị (kho không đủ số lượng sẵn sàng).")
                                ->warning();
                        } else {
                            $notification->title('Đã tạo phiếu xuất kho (Chưa gán được thiết bị)!')
                                ->body("Phiếu xuất kho {$code} đã được tạo nhưng kho hiện không có thiết bị nào ở trạng thái sẵn sàng để gán.")
                                ->warning();
                        }
                    } else {
                        $notification->title('Đã tạo phiếu xuất kho!')
                            ->body("Phiếu xuất kho {$code} cho đơn hàng {$record->order_no} đã được tạo thành công.")
                            ->success();
                    }
                    $notification->send();
                });
            });
    }
}
