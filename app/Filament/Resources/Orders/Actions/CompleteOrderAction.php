<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Enums\RepairResultStatus;
use App\Models\Order;
use App\Models\RepairLog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CompleteOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'complete_order';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Hoàn tất Đơn hàng')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Returned)
            ->requiresConfirmation()
            ->modalHeading('Hoàn tất & Đóng đơn hàng')
            ->modalDescription(fn (Order $record) => "Xác nhận đơn hàng {$record->order_no} đã thu hồi đầy đủ thiết bị về kho và hoàn tất tất cả các khâu để đóng đơn?")
            ->modalSubmitActionLabel('Xác nhận Hoàn tất')
            ->action(function (Order $record): void {
                // Không cho đóng đơn nếu còn thiết bị đang bảo trì chưa xong
                $pendingRepairs = RepairLog::whereHas('asset', function ($query) use ($record) {
                    $query->where('current_warehouse_id', $record->warehouse_id);
                })
                    ->where('result_status', RepairResultStatus::Pending)
                    ->count();

                if ($pendingRepairs > 0) {
                    Notification::make()
                        ->title('Còn thiết bị đang bảo trì')
                        ->body("Đơn hàng {$record->order_no} có {$pendingRepairs} thiết bị vẫn đang ở trạng thái bảo trì/chưa xong. Vui lòng hoàn tất sửa chữa trước khi đóng đơn.")
                        ->warning()
                        ->send();

                    return;
                }

                $record->update(['status' => OrderStatus::Completed]);

                Notification::make()
                    ->title('Đơn hàng đã hoàn tất!')
                    ->body("Đơn hàng {$record->order_no} đã được chuyển sang trạng thái Hoàn tất thành công.")
                    ->success()
                    ->send();
            });
    }
}
