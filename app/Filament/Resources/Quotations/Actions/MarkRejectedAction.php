<?php

namespace App\Filament\Resources\Quotations\Actions;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class MarkRejectedAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'mark_rejected';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('MarkRejected:Quotation')
            ->label('Đánh dấu Bị từ chối')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Quotation $record): bool => in_array($record->status, [
                QuotationStatus::Draft,
                QuotationStatus::Sent,
            ]))
            ->modalHeading('Ghi nhận Báo giá không chốt được')
            ->modalDescription('Vui lòng chọn lý do khách hàng từ chối để tối ưu tỷ lệ chốt sales.')
            ->form([
                Select::make('lost_reason_select')
                    ->label('Lý do thất thoát chính')
                    ->options([
                        'Giá quá cao so với ngân sách' => 'Giá quá cao so với ngân sách',
                        'Thiếu số lượng cabinet trong kho' => 'Thiếu số lượng cabinet trong kho',
                        'Đối thủ cung cấp gói dịch vụ rẻ hơn' => 'Đối thủ cung cấp gói dịch vụ rẻ hơn',
                        'Khách dời lịch / hủy sự kiện' => 'Khách dời lịch / hủy sự kiện',
                        'Không đạt yêu cầu kỹ thuật đặc thù' => 'Không đạt yêu cầu kỹ thuật đặc thù',
                        'Khác' => 'Khác',
                    ])
                    ->required(),
                Textarea::make('lost_reason_note')
                    ->label('Ghi chú chi tiết thêm')
                    ->placeholder('Nhập chi tiết lý do từ chối (tùy chọn)...'),
            ])
            ->action(function (Quotation $record, array $data): void {
                $reason = $data['lost_reason_select'];
                if (! empty($data['lost_reason_note'])) {
                    $reason .= ' — '.$data['lost_reason_note'];
                }

                $record->update([
                    'status' => QuotationStatus::Rejected,
                    'lost_reason' => $reason,
                ]);

                Notification::make()
                    ->title('Đã cập nhật trạng thái báo giá')
                    ->body("Báo giá {$record->code} đã chuyển sang Bị từ chối.")
                    ->warning()
                    ->send();
            });
    }
}
