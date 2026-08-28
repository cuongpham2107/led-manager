<?php

namespace App\Filament\Resources\Contracts\Actions;

use App\Enums\ContractStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Models\Contract;
use App\Models\Payment;
use App\Services\CodeGeneratorService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class CreatePartialPaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'createPartialPayment';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Tạo thanh toán đợt')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->modalHeading('Tạo phiếu thu thanh toán đợt')
            ->modalDescription(fn (Contract $record) => "Tạo phiếu thu cho hợp đồng {$record->code} (còn thiếu: ".number_format((float) $record->remaining_debt, 0, ',', '.').' đ)')
            ->modalSubmitActionLabel('Xác nhận thu')
            ->schema([
                TextInput::make('code')
                    ->label('Mã phiếu thu')
                    ->default(fn () => CodeGeneratorService::generate('PAY', 'payments'))
                    ->required(),
                Select::make('type')
                    ->label('Loại thanh toán')
                    ->options(PaymentType::class)
                    ->default(PaymentType::Partial)
                    ->required(),
                TextInput::make('amount')
                    ->label('Số tiền thu')
                    ->default(fn (Contract $record) => (int) $record->remaining_debt)
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->suffix(' đ')
                    ->required(),
                Select::make('method')
                    ->label('Hình thức thanh toán')
                    ->options(PaymentMethod::class)
                    ->default(PaymentMethod::BankTransfer)
                    ->required(),
                DatePicker::make('payment_date')
                    ->label('Ngày thu tiền')
                    ->native(false)
                    ->default(now())
                    ->required(),
                TextInput::make('reference')
                    ->label('Số hóa đơn / Mã GD tham chiếu')
                    ->placeholder('VD: FT260812345678 / HDGT-00124'),
                Textarea::make('note')
                    ->label('Nội dung / Ghi chú')
                    ->default(fn (Contract $record) => "Thanh toán hợp đồng {$record->code}")
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (Contract $record, array $data): void {
                $record->load('payments');
                $totalPaid = $record->payments->sum('amount');
                $newTotal = $totalPaid + (float) $data['amount'];

                if ($newTotal > (float) $record->contract_value + 0.01) {
                    Notification::make()
                        ->title('Vượt quá giá trị hợp đồng')
                        ->body('Tổng tiền đã thu ('.number_format($totalPaid, 0, ',', '.').' đ) cộng với lần này ('.number_format((float) $data['amount'], 0, ',', '.').' đ) vượt quá giá trị hợp đồng ('.number_format((float) $record->contract_value, 0, ',', '.').' đ).')
                        ->danger()
                        ->send();

                    return;
                }

                $payment = Payment::create([
                    'code' => $data['code'],
                    'contract_id' => $record->id,
                    'order_id' => $record->order_id,
                    'customer_id' => $record->customer_id,
                    'type' => $data['type'],
                    'method' => $data['method'],
                    'amount' => $data['amount'],
                    'payment_date' => $data['payment_date'],
                    'reference' => $data['reference'] ?? null,
                    'note' => $data['note'] ?? null,
                    'received_by' => Auth::id(),
                ]);

                // Sync contract status based on payment progress
                if ((float) $record->contract_value > 0 && $newTotal >= (float) $record->contract_value) {
                    $record->update(['status' => ContractStatus::Completed]);
                } elseif ($record->deposit_amount > 0 && $newTotal >= (float) $record->deposit_amount) {
                    $record->update(['status' => ContractStatus::DepositReceived]);
                }

                Notification::make()
                    ->title('Đã tạo phiếu thu!')
                    ->body("Phiếu thu {$payment->code} (".number_format((float) $payment->amount, 0, ',', '.')." đ) đã được ghi nhận cho hợp đồng {$record->code}. Còn thiếu ".number_format(max(0, (float) $record->remaining_debt), 0, ',', '.').' đ.')
                    ->success()
                    ->send();
            });
    }
}
