<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MilestoneType: string implements HasColor, HasIcon, HasLabel
{
    case Delivery = 'delivery';
    case Setup = 'setup';
    case Testing = 'testing';
    case EventStart = 'event_start';
    case EventEnd = 'event_end';
    case Teardown = 'teardown';
    case ReturnToWarehouse = 'return';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Delivery => 'Giao hàng đến địa điểm',
            self::Setup => 'Lắp đặt khung & màn hình',
            self::Testing => 'Test màu sắc & tín hiệu 4K',
            self::EventStart => 'Bắt đầu diễn ra sự kiện',
            self::EventEnd => 'Kết thúc sự kiện',
            self::Teardown => 'Tháo dỡ màn hình & đóng thùng',
            self::ReturnToWarehouse => 'Vận chuyển về lại kho',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Delivery => 'info',
            self::Setup => 'warning',
            self::Testing => 'primary',
            self::EventStart => 'success',
            self::EventEnd => 'gray',
            self::Teardown => 'danger',
            self::ReturnToWarehouse => 'emerald',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Delivery => 'heroicon-m-truck',
            self::Setup => 'heroicon-m-wrench-screwdriver',
            self::Testing => 'heroicon-m-tv',
            self::EventStart => 'heroicon-m-play',
            self::EventEnd => 'heroicon-m-stop',
            self::Teardown => 'heroicon-m-archive-box-arrow-down',
            self::ReturnToWarehouse => 'heroicon-m-building-office-2',
        };
    }
}
