<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ContractStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case SentForApproval = 'sent_for_approval';
    case Approved = 'approved';
    case Signed = 'signed';
    case DepositReceived = 'deposit_received';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Dự thảo',
            self::Sent => 'Đã gửi khách',
            self::SentForApproval => 'Chờ duyệt',
            self::Approved => 'Đã duyệt',
            self::Signed => 'Đã ký kết',
            self::DepositReceived => 'Đã thu cọc',
            self::Active => 'Đang hiệu lực',
            self::Completed => 'Hoàn tất',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::SentForApproval => 'warning',
            self::Approved => 'success',
            self::Signed => 'warning',
            self::DepositReceived => 'warning',
            self::Active => 'success',
            self::Completed => 'emerald',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-m-pencil-square',
            self::Sent => 'heroicon-m-paper-airplane',
            self::SentForApproval => 'heroicon-m-clock',
            self::Approved => 'heroicon-m-check-badge',
            self::Signed => 'heroicon-m-check-badge',
            self::DepositReceived => 'heroicon-m-banknotes',
            self::Active => 'heroicon-m-play-circle',
            self::Completed => 'heroicon-m-check-circle',
            self::Cancelled => 'heroicon-m-x-circle',
        };
    }
}
