<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\AssignmentRole;
use App\Enums\OrderStatus;
use App\Models\EventAssignment;
use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class AssignCrewAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'assign_crew';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Phân công nhân sự')
            ->icon(Heroicon::OutlinedUserGroup)
            ->color('info')
            ->visible(fn (Order $record): bool => ! in_array($record->status, [OrderStatus::Cancelled, OrderStatus::Completed]))
            ->modalHeading(fn (Order $record): string => "Phân công kỹ thuật viên — Đơn hàng {$record->order_no}")
            ->modalDescription('Chỉ định nhân viên, kỹ thuật viên, tài xế phụ trách sự kiện')
            ->modalSubmitActionLabel('Lưu phân công')
            ->form(fn (Order $record): array => [
                Select::make('user_id')
                    ->label('Nhân sự / Kỹ thuật viên')
                    ->relationship('salesUser', 'name')
                    ->options(User::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('role')
                    ->label('Vai trò trong sự kiện')
                    ->options(AssignmentRole::class)
                    ->default(AssignmentRole::Technician)
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Ngày bắt đầu')
                    ->default($record->request_date?->toDateString() ?? now()->toDateString())
                    ->native(false)
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Ngày kết thúc')
                    ->default($record->expected_return_date?->toDateString() ?? $record->request_date?->toDateString() ?? now()->toDateString())
                    ->native(false)
                    ->required(),
                Textarea::make('note')
                    ->label('Nhiệm vụ / Lưu ý hiện trường')
                    ->placeholder('VD: Phụ trách lắp ráp khung truss và setup module LED P3.91...')
                    ->rows(2),
            ])
            ->action(function (Order $record, array $data): void {
                EventAssignment::updateOrCreate(
                    [
                        'order_id' => $record->id,
                        'user_id' => $data['user_id'],
                        'role' => $data['role'],
                    ],
                    [
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'note' => $data['note'] ?? null,
                    ]
                );

                Notification::make()
                    ->title('Phân công nhân sự thành công')
                    ->body("Đã phân công nhân sự cho đơn hàng {$record->order_no}.")
                    ->success()
                    ->send();
            });
    }
}
