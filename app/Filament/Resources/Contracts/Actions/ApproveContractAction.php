<?php

namespace App\Filament\Resources\Contracts\Actions;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ApproveContractAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'approve_contract';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Duyệt hợp đồng')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (Contract $record): bool => $record->status === ContractStatus::SentForApproval)
            ->requiresConfirmation()
            ->modalHeading('Duyệt hợp đồng')
            ->modalDescription(fn (Contract $record) => "Phê duyệt hợp đồng {$record->code}? Sau khi duyệt, hợp đồng chuyển sang trạng thái 'Đã duyệt'.")
            ->modalSubmitActionLabel('Xác nhận duyệt')
            ->action(function (Contract $record): void {
                $record->update(['status' => ContractStatus::Approved]);

                Notification::make()
                    ->title('Đã duyệt hợp đồng!')
                    ->body("Hợp đồng {$record->code} đã được phê duyệt.")
                    ->success()
                    ->send();
            });
    }
}
