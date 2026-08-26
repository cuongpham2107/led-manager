<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasColor, HasIcon, HasLabel
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Other = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::BankTransfer => 'Chuyển khoản ngân hàng',
            self::Cash => 'Tiền mặt',
            self::Other => 'Hình thức khác',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BankTransfer => 'primary',
            self::Cash => 'emerald',
            self::Other => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::BankTransfer => 'heroicon-m-building-library',
            self::Cash => 'heroicon-m-currency-dollar',
            self::Other => 'heroicon-m-ellipsis-horizontal',
        };
    }
}
