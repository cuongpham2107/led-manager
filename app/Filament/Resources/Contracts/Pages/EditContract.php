<?php

namespace App\Filament\Resources\Contracts\Pages;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Filament\Resources\Contracts\ContractResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;

    public static bool $formActionsAreSticky = true;

    public static string|Alignment $formActionsAlignment = Alignment::End;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCreateDepositPaymentAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreateDepositPaymentAction(): Action
    {
        return Action::make('createDepositPayment')
            ->label('Tạo thanh toán đặt cọc')
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->extraAttributes([
                'class' => '!text-white [&_svg]:!text-white [&_span]:!text-white font-semibold',
            ])
            ->modalHeading('Tạo phiếu thu đặt cọc')
            ->modalDescription(fn () => "Tạo phiếu thu tiền cọc cho hợp đồng {$this->record->code}")
            ->modalSubmitActionLabel('Xác nhận thu cọc')
            ->schema([
                TextInput::make('code')
                    ->label('Mã phiếu thu')
                    ->default(function () {
                        $count = Payment::count() + 1;
                        $code = 'PAY-'.date('ym').'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
                        while (Payment::where('code', $code)->exists()) {
                            $count++;
                            $code = 'PAY-'.date('ym').'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
                        }

                        return $code;
                    })
                    ->required(),
                TextInput::make('amount')
                    ->label('Số tiền thu cọc')
                    ->default(fn () => $this->record->deposit_amount ?: 0)
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
                    ->default(fn () => "Thu tiền đặt cọc hợp đồng {$this->record->code}")
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $payment = Payment::create([
                    'code' => $data['code'],
                    'contract_id' => $this->record->id,
                    'order_id' => $this->record->order_id,
                    'customer_id' => $this->record->customer_id,
                    'type' => PaymentType::Deposit,
                    'method' => $data['method'],
                    'amount' => $data['amount'],
                    'payment_date' => $data['payment_date'],
                    'reference' => $data['reference'] ?? null,
                    'note' => $data['note'] ?? null,
                    'received_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Đã tạo phiếu thu đặt cọc!')
                    ->body("Phiếu thu {$payment->code} (".number_format((float) $payment->amount, 0, ',', '.')." đ) đã được ghi nhận cho hợp đồng {$this->record->code}.")
                    ->success()
                    ->send();
            });
    }
}
