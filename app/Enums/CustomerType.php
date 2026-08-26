<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CustomerType: string implements HasColor, HasIcon, HasLabel
{
    case Corporate = 'corporate';
    case Agency = 'agency';
    case Individual = 'individual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Corporate => 'Doanh nghiệp (Corporate)',
            self::Agency => 'Agency sự kiện (Event Agency)',
            self::Individual => 'Cá nhân (Individual)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Corporate => 'primary',
            self::Agency => 'warning',
            self::Individual => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Corporate => 'heroicon-o-building-office',
            self::Agency => 'heroicon-o-sparkles',
            self::Individual => 'heroicon-o-user',
        };
    }
}
