<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeviceUnit: string implements HasLabel
{
    case Piece = 'piece';
    case Set = 'set';
    case Box = 'box';
    case Bar = 'bar';
    case Cable = 'cable';
    case Meter = 'meter';
    case Roll = 'roll';

    public function getLabel(): string
    {
        return match ($this) {
            self::Piece => 'Tấm / Chiếc',
            self::Set => 'Bộ',
            self::Box => 'Thùng',
            self::Bar => 'Thanh',
            self::Cable => 'Sợi / Dây',
            self::Meter => 'Mét',
            self::Roll => 'Cuộn',
        };
    }
}
