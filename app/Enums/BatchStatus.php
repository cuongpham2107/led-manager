<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BatchStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Dispatched = 'dispatched';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xử lý (Pending)',
            self::InProgress => 'Đang quét PDA (In Progress)',
            self::Dispatched => 'Đã xuất kho (Dispatched)',
            self::Completed => 'Đã hoàn tất (Completed)',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'warning',
            self::Dispatched, self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::InProgress => 'heroicon-o-viewfinder-circle',
            self::Dispatched, self::Completed => 'heroicon-o-check-badge',
            self::Cancelled => 'heroicon-o-x-mark',
        };
    }
}
