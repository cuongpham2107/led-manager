<?php

namespace App\Filament\Resources\Contracts\Actions;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class SendForApprovalAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'send_for_approval';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Trình duyệt')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->visible(fn (Contract $record): bool => in_array($record->status, [ContractStatus::Draft, ContractStatus::Signed]))
            ->requiresConfirmation()
            ->modalHeading('Trình duyệt hợp đồng')
            ->modalDescription(fn (Contract $record) => "Gửi hợp đồng {$record->code} lên cấp duyệt nội bộ?")
            ->modalSubmitActionLabel('Gửi duyệt')
            ->action(function (Contract $record): void {
                $record->update(['status' => ContractStatus::SentForApproval]);

                Notification::make()
                    ->title('Đã gửi duyệt!')
                    ->body("Hợp đồng {$record->code} đang chờ duyệt.")
                    ->warning()
                    ->send();
            });
    }
}
