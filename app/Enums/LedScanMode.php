<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LedScanMode: string implements HasLabel
{
    case Static = 'static';
    case Quarter = '1/4';
    case Eighth = '1/8';
    case Sixteenth = '1/16';
    case ThirtySecond = '1/32';

    public function getLabel(): string
    {
        return match ($this) {
            self::Static => 'Tĩnh (Static)',
            default => "{$this->value} scan",
        };
    }
}
