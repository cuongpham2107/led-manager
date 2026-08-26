<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MilestoneStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Chờ thực hiện',
            self::InProgress => 'Đang thực hiện',
            self::Completed => 'Đã hoàn thành',
            self::Skipped => 'Bỏ qua',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Skipped => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-m-clock',
            self::InProgress => 'heroicon-m-arrow-path',
            self::Completed => 'heroicon-m-check-circle',
            self::Skipped => 'heroicon-m-forward',
        };
    }
}
