<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Enums\OrderStatus;
use App\Models\EventMilestone;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ManageTimelineAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'manage_timeline';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('ManageTimeline:Order')
            ->label('Mốc tiến độ thi công')
            ->icon(Heroicon::OutlinedClock)
            ->color('warning')
            ->visible(fn (Order $record): bool => ! in_array($record->status, [OrderStatus::Draft, OrderStatus::Cancelled, OrderStatus::Completed]))
            ->modalHeading(fn (Order $record): string => "Thêm mốc lịch trình thi công — Đơn hàng {$record->order_no}")
            ->modalDescription('Cập nhật mốc thời gian giao hàng, lắp đặt, chạy thử, sự kiện...')
            ->modalSubmitActionLabel('Tạo mốc')
            ->form(fn (Order $record): array => [
                Select::make('type')
                    ->label('Hạng mục công việc')
                    ->options(MilestoneType::class)
                    ->default(MilestoneType::Setup)
                    ->required(),
                DateTimePicker::make('planned_at')
                    ->label('Thời gian kế hoạch')
                    ->default(now())
                    ->native(false)
                    ->required(),
                DateTimePicker::make('actual_at')
                    ->label('Thời gian thực tế (nếu có)')
                    ->native(false),
                Select::make('status')
                    ->label('Trạng thái')
                    ->options(MilestoneStatus::class)
                    ->default(MilestoneStatus::Pending)
                    ->required(),
                Textarea::make('note')
                    ->label('Ghi chú tiến độ')
                    ->placeholder('VD: Bàn giao mặt bằng chậm 1h do thời tiết...')
                    ->rows(2),
            ])
            ->action(function (Order $record, array $data): void {
                EventMilestone::create([
                    'order_id' => $record->id,
                    'type' => $data['type'],
                    'planned_at' => $data['planned_at'],
                    'actual_at' => $data['actual_at'] ?? null,
                    'status' => $data['status'],
                    'note' => $data['note'] ?? null,
                ]);

                $record->refresh();

                Notification::make()
                    ->title('Thêm mốc tiến độ thành công')
                    ->body("Đã thêm mốc {$data['type']} cho đơn hàng {$record->order_no}.")
                    ->success()
                    ->send();
            });
    }
}
