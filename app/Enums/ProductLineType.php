<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductLineType: string implements HasColor, HasLabel
{
    case Panel = 'panel';
    case Controller = 'controller';

    public function getLabel(): string
    {
        return match ($this) {
            self::Panel => 'Tấm LED (Cabinet)',
            self::Controller => 'Đầu phát (Bộ điều khiển)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Panel => 'info',
            self::Controller => 'warning',
        };
    }
}
