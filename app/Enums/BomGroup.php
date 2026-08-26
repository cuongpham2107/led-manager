<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BomGroup: string implements HasColor, HasIcon, HasLabel
{
    case Core = 'core';
    case Control = 'control';
    case Rigging = 'rigging';
    case Cabling = 'cabling';

    public function getLabel(): string
    {
        return match ($this) {
            self::Core => 'Core',
            self::Control => 'Control',
            self::Rigging => 'Rigging',
            self::Cabling => 'Cabling',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Core => 'gray',
            self::Control => 'gray',
            self::Rigging => 'gray',
            self::Cabling => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Core => 'heroicon-o-cube',
            self::Control => 'heroicon-o-cpu-chip',
            self::Rigging => 'heroicon-o-wrench',
            self::Cabling => 'heroicon-o-bolt',
        };
    }
}
