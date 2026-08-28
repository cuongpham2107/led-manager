<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DispatchOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'dispatch_order';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Xuất kho đi sự kiện')
            ->icon('heroicon-o-truck')
            ->color('info')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::OutboundCreated && $record->checkoutBatches->isNotEmpty())
            ->requiresConfirmation()
            ->modalHeading('Xác nhận Xuất kho đi sự kiện')
            ->modalDescription(fn (Order $record) => "Chuyển đơn hàng {$record->order_no} sang trạng thái 'Đã xuất kho đi sự kiện' và chuyển toàn bộ thiết bị trong đợt xuất sang trạng thái 'Đang chạy sự kiện'?")
            ->modalSubmitActionLabel('Xác nhận Xuất kho')
            ->action(function (Order $record): void {
                // Validate: tất cả thiết bị trong các đợt xuất phải đã được quét (is_dispatched)
                $unscanned = 0;
                foreach ($record->checkoutBatches as $batch) {
                    $unscanned += $batch->items()->where('is_dispatched', false)->count();
                }

                if ($unscanned > 0) {
                    Notification::make()
                        ->title('Chưa quét đủ thiết bị xuất kho')
                        ->body("Còn {$unscanned} thiết bị chưa được quét (Scan to Dispatch) trên các phiếu xuất kho. Vui lòng hoàn tất quét trước khi xuất đi sự kiện.")
                        ->danger()
                        ->send();

                    return;
                }

                DB::transaction(function () use ($record) {
                    foreach ($record->checkoutBatches as $batch) {
                        $batch->update([
                            'status' => BatchStatus::Dispatched,
                            'dispatched_at' => now(),
                        ]);
                        $batch->loadMissing('items.asset');
                        foreach ($batch->items as $item) {
                            if ($item->asset) {
                                $oldStatus = $item->asset->current_status;
                                $item->asset->update(['current_status' => AssetStatus::InEvent]);

                                AssetStatusLog::create([
                                    'asset_id' => $item->asset->id,
                                    'from_status' => $oldStatus,
                                    'to_status' => AssetStatus::InEvent,
                                    'from_warehouse_id' => $item->asset->current_warehouse_id,
                                    'to_warehouse_id' => null,
                                    'source_type' => CheckoutBatch::class,
                                    'source_id' => $batch->id,
                                    'changed_by' => Auth::id(),
                                    'note' => "Xuất kho đi sự kiện '{$record->event}' (Đơn hàng {$record->order_no})",
                                    'created_at' => now(),
                                ]);
                            }
                        }
                    }

                    $record->update(['status' => OrderStatus::Dispatched]);

                    Notification::make()
                        ->title('Đã xuất kho đi sự kiện!')
                        ->body("Đơn hàng {$record->order_no} đã được chuyển sang trạng thái Đã xuất kho.")
                        ->success()
                        ->send();
                });
            });
    }
}
