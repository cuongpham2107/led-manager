<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderAmendment;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ChangeOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'change_order';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Thay đổi đơn hàng')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->visible(fn (Order $record): bool => ! in_array($record->status, [OrderStatus::Cancelled, OrderStatus::Completed]))
            ->modalHeading(fn (Order $record) => 'Phụ lục thay đổi đơn hàng: '.$record->order_no)
            ->modalDescription('Ghi nhận thay đổi phát sinh (gia hạn ngày trả, ghi chú, điều chỉnh). Hệ thống sẽ lưu lịch sử phụ lục và cập nhật đơn hàng.')
            ->modalSubmitActionLabel('Lưu thay đổi')
            ->form([
                Select::make('type')
                    ->label('Loại thay đổi')
                    ->options([
                        'extend_return' => 'Gia hạn ngày trả dự kiến',
                        'add_note' => 'Bổ sung ghi chú',
                        'add_items' => 'Phát sinh thêm thiết bị',
                        'price_adjustment' => 'Điều chỉnh giá',
                    ])
                    ->default('extend_return')
                    ->required(),
                DatePicker::make('new_expected_return_date')
                    ->label('Ngày trả dự kiến mới')
                    ->native(false)
                    ->visible(fn (callable $get) => $get('type') === 'extend_return'),
                Textarea::make('description')
                    ->label('Mô tả thay đổi')
                    ->required()
                    ->placeholder('VD: Khách gia hạn thêm 2 ngày do sự kiện kéo dài...')
                    ->rows(3),
            ])
            ->action(function (Order $record, array $data): void {
                $oldReturnDate = $record->expected_return_date;

                if ($data['type'] === 'extend_return' && ! empty($data['new_expected_return_date'])) {
                    $record->update(['expected_return_date' => $data['new_expected_return_date']]);
                }

                OrderAmendment::create([
                    'order_id' => $record->id,
                    'type' => $data['type'],
                    'description' => $data['description'],
                    'old_expected_return_date' => $oldReturnDate,
                    'new_expected_return_date' => $data['new_expected_return_date'] ?? null,
                    'old_value' => ['expected_return_date' => $oldReturnDate?->toDateString()],
                    'new_value' => ['expected_return_date' => $record->expected_return_date?->toDateString()],
                    'created_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Đã ghi nhận thay đổi đơn hàng!')
                    ->body("Phụ lục cho đơn hàng {$record->order_no} đã được lưu.".($data['type'] === 'extend_return' && ! empty($data['new_expected_return_date']) ? " Ngày trả mới: {$record->expected_return_date->format('d/m/Y')}." : ''))
                    ->success()
                    ->send();
            });
    }
}
