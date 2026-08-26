<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReturnGrade: string implements HasColor, HasIcon, HasLabel
{
    case Normal = 'normal';
    case Damaged = 'damaged';

    public function getLabel(): string
    {
        return match ($this) {
            self::Normal => 'Bình thường (Đạt chuẩn)',
            self::Damaged => 'Hỏng hóc / Lỗi (Cần bảo dưỡng)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Normal => 'success',
            self::Damaged => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Normal => 'heroicon-o-check-circle',
            self::Damaged => 'heroicon-o-exclamation-triangle',
        };
    }
}
