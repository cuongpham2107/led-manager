<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReturnBatchStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Chờ nhận hàng',
            self::InProgress => 'Đang phân loại kiểm tra (Grading)',
            self::Completed => 'Đã hoàn tất nhập kho',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'warning',
            self::Completed => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::InProgress => 'heroicon-o-viewfinder-circle',
            self::Completed => 'heroicon-o-check-badge',
        };
    }
}
