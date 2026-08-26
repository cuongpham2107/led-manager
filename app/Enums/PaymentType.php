<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentType: string implements HasColor, HasIcon, HasLabel
{
    case Deposit = 'deposit';
    case Partial = 'partial';
    case Final = 'final';
    case Refund = 'refund';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Deposit => 'Tiền đặt cọc',
            self::Partial => 'Thanh toán đợt',
            self::Final => 'Quyết toán cuối',
            self::Refund => 'Hoàn tiền',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Deposit => 'warning',
            self::Partial => 'info',
            self::Final => 'success',
            self::Refund => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Deposit => 'heroicon-m-banknotes',
            self::Partial => 'heroicon-m-arrow-path',
            self::Final => 'heroicon-m-check-circle',
            self::Refund => 'heroicon-m-arrow-uturn-left',
        };
    }
}
