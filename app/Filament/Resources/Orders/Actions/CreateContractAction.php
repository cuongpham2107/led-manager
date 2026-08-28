<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\ContractStatus;
use App\Enums\OrderStatus;
use App\Models\Contract;
use App\Models\Order;
use App\Services\CodeGeneratorService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateContractAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'create_contract';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Tạo Hợp đồng')
            ->icon('heroicon-o-document-plus')
            ->color('primary')
            ->visible(fn (Order $record): bool => ! in_array($record->status, [OrderStatus::Cancelled]) && $record->contracts->isEmpty())
            ->form([
                TextInput::make('deposit_percent')
                    ->label('Tỷ lệ đặt cọc (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(50)
                    ->required(),
            ])
            ->action(function (Order $record, array $data): void {
                $contractCode = CodeGeneratorService::generate('HD', 'contracts');

                $depositPercent = (float) ($data['deposit_percent'] ?? 50);
                $depositAmount = round((float) $record->value * $depositPercent / 100);

                Contract::create([
                    'code' => $contractCode,
                    'quotation_id' => $record->quotation_id,
                    'customer_id' => $record->customer_id,
                    'order_id' => $record->id,
                    'title' => 'Hợp đồng cho thuê màn hình LED: '.($record->event ?: $record->order_no),
                    'signed_date' => now()->toDateString(),
                    'start_date' => $record->request_date,
                    'end_date' => $record->expected_return_date,
                    'contract_value' => $record->value,
                    'deposit_percent' => $depositPercent,
                    'deposit_amount' => $depositAmount,
                    'status' => ContractStatus::Draft,
                    'sales_user_id' => $record->sales_user_id,
                    'created_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Đã tạo hợp đồng thành công!')
                    ->body("Hợp đồng {$contractCode} đã được tạo tự động cho đơn hàng {$record->order_no} (Đặt cọc {$depositPercent}% = ".number_format($depositAmount, 0, ',', '.').' đ).')
                    ->success()
                    ->send();
            });
    }
}
