<?php

namespace App\Filament\Resources\CheckoutBatches\Actions;

use App\Enums\BatchStatus;
use App\Enums\ReturnGrade;
use App\Models\CheckoutBatch;
use App\Services\ReturnProcessingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;

class CreateReturnBatchAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'create_return_batch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $service = app(ReturnProcessingService::class);

        $this
            ->label('Tạo Đợt Trả Kho')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('primary')
            ->visible(fn (CheckoutBatch $record): bool => $record->returnBatches->isEmpty() && in_array($record->status, [BatchStatus::Dispatched, BatchStatus::Completed, BatchStatus::InProgress]))
            ->modalHeading(fn (CheckoutBatch $record) => 'Tạo Đợt Trả Kho: '.$record->code)
            ->modalDescription('Kiểm đếm thiết bị trả về từ đợt xuất này, phân loại tình trạng để cập nhật kho. Cùng luồng xử lý với luồng trả kho từ Đơn hàng.')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitActionLabel('Xác nhận Nhập trả kho')
            ->fillForm(fn (CheckoutBatch $record): array => $service->fillFormData(collect([$record])))
            ->form($service->formSchema())
            ->action(function (CheckoutBatch $record, array $data) use ($service): void {
                if (! $order = $record->order) {
                    Notification::make()
                        ->title('Không thể tạo đợt trả kho')
                        ->body('Đợt xuất kho này không liên kết với đơn hàng nào.')
                        ->danger()
                        ->send();

                    return;
                }

                $returnBatch = $service->processReturn(
                    order: $order,
                    items: $data['items'],
                    receivedBy: $data['received_by'] ?? null,
                    returnDate: $data['return_date'] ?? null,
                    note: $data['note'] ?? null,
                    completeBatchIds: [$record->id],
                );

                $normalCount = $returnBatch->items()->where('is_received', true)->where('grade', ReturnGrade::Normal)->count();
                $damagedCount = $returnBatch->items()->where('grade', ReturnGrade::Damaged)->count();
                $missingCount = $returnBatch->items()->where('is_received', false)->count();

                $body = "Phiếu trả kho {$returnBatch->code} đã hoàn tất: {$normalCount} thiết bị đạt chuẩn";
                if ($damagedCount > 0) {
                    $body .= ", {$damagedCount} chuyển bảo dưỡng";
                }

                if ($missingCount > 0) {
                    $body .= ", {$missingCount} KHÔNG trả về (đánh dấu Mất)";
                }

                $body .= '.';

                Notification::make()
                    ->title('Thu hồi trả kho thành công!')
                    ->body($body)
                    ->success()
                    ->send();
            });
    }
}
