<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProductEnvironment: string implements HasColor, HasIcon, HasLabel
{
    case Indoor = 'indoor';
    case Outdoor = 'outdoor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Indoor => 'Trong nhà (Indoor)',
            self::Outdoor => 'Ngoài trời (Outdoor - Chống nước)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Indoor => 'info',
            self::Outdoor => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Indoor => 'heroicon-o-home',
            self::Outdoor => 'heroicon-o-sun',
        };
    }
}
