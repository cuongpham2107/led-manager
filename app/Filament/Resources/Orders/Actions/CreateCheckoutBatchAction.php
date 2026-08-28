<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Models\CheckoutBatch;
use App\Models\Order;
use App\Services\CodeGeneratorService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateCheckoutBatchAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'create_checkout_batch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Tạo Đợt Xuất Kho')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Draft && $record->checkoutBatches->isEmpty())
            ->action(function (Order $record): void {
                $code = CodeGeneratorService::generate('OUT', 'checkout_batches');

                $deviceTypeId = $record->device_type_id
                    ?? $record->items->first()?->device_type_id
                    ?? null;

                CheckoutBatch::create([
                    'code' => $code,
                    'order_id' => $record->id,
                    'customer_id' => $record->customer_id,
                    'warehouse_id' => $record->warehouse_id,
                    'required_area_m2' => $record->area_m2,
                    'device_type_id' => $deviceTypeId,
                    'expected_return_date' => $record->expected_return_date,
                    'status' => BatchStatus::Pending,
                    'created_by' => Auth::id(),
                ]);

                $record->update([
                    'status' => OrderStatus::OutboundCreated,
                ]);

                Notification::make()
                    ->title('Đã tạo phiếu xuất kho!')
                    ->body("Phiếu xuất kho {$code} cho đơn hàng {$record->order_no} đã được tạo thành công.")
                    ->success()
                    ->send();
            });
    }
}
