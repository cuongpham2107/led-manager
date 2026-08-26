<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Models\Contract;
use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin phiếu thu / thanh toán')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Mã phiếu thu')
                                    ->default(fn () => 'PAY-'.date('ym').'-'.str_pad((string) (Payment::count() + 1), 2, '0', STR_PAD_LEFT))
                                    ->required(),
                                Select::make('contract_id')
                                    ->label('Hợp đồng thanh toán')
                                    ->relationship('contract', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $contract = Contract::find($state);
                                            if ($contract) {
                                                $set('customer_id', $contract->customer_id);
                                                $set('order_id', $contract->order_id);
                                                if ($contract->payments()->count() === 0) {
                                                    $set('type', PaymentType::Deposit);
                                                    $set('amount', $contract->deposit_amount);
                                                } else {
                                                    $set('type', PaymentType::Partial);
                                                    $set('amount', $contract->remaining_debt);
                                                }
                                            }
                                        }
                                    }),
                                Select::make('customer_id')
                                    ->label('Khách hàng')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                        Grid::make(4)
                            ->schema([
                                Select::make('type')
                                    ->label('Loại thanh toán')
                                    ->options(PaymentType::class)
                                    ->default(PaymentType::Deposit)
                                    ->required(),
                                Select::make('method')
                                    ->label('Hình thức')
                                    ->options(PaymentMethod::class)
                                    ->default(PaymentMethod::BankTransfer)
                                    ->required(),
                                TextInput::make('amount')
                                    ->label('Số tiền thu')
                                    ->required()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->default(0),
                                DatePicker::make('payment_date')
                                    ->label('Ngày thu tiền')
                                    ->native(false)
                                    ->default(now())
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('reference')
                                    ->label('Số hóa đơn / Mã tham chiếu GD')
                                    ->placeholder('VD: FT260812345678 / HDGT-00124'),
                                Select::make('received_by')
                                    ->label('Người thu tiền / Kế toán')
                                    ->relationship('receiver', 'name')
                                    ->default(fn () => Auth::id())
                                    ->searchable()
                                    ->preload(),
                            ]),
                        Textarea::make('note')
                            ->label('Nội dung thanh toán / Ghi chú')
                            ->placeholder('VD: Đặt cọc 50% cho sự kiện màn hình LED VinFast...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
