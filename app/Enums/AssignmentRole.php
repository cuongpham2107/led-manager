<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AssignmentRole: string implements HasColor, HasIcon, HasLabel
{
    case Lead = 'lead';
    case Technician = 'technician';
    case Driver = 'driver';
    case Helper = 'helper';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Lead => 'Trưởng nhóm Kỹ thuật (Lead Tech)',
            self::Technician => 'Kỹ thuật viên vận hành',
            self::Driver => 'Tài xế vận chuyển',
            self::Helper => 'Nhân công phụ việc (Helper)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Lead => 'danger',
            self::Technician => 'info',
            self::Driver => 'warning',
            self::Helper => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Lead => 'heroicon-m-user-group',
            self::Technician => 'heroicon-m-wrench-screwdriver',
            self::Driver => 'heroicon-m-truck',
            self::Helper => 'heroicon-m-hand-raised',
        };
    }
}
