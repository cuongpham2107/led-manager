<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ContractStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Signed = 'signed';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Dự thảo',
            self::Sent => 'Đã gửi khách',
            self::Signed => 'Đã ký kết',
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
            self::Signed => 'warning',
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
            self::Signed => 'heroicon-m-check-badge',
            self::Active => 'heroicon-m-play-circle',
            self::Completed => 'heroicon-m-check-circle',
            self::Cancelled => 'heroicon-m-x-circle',
        };
    }
}
