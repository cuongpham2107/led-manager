<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AssetStatus: string implements HasColor, HasIcon, HasLabel
{
    case Ready = 'ready';
    case InEvent = 'in_event';
    case InTransit = 'in_transit';
    case Repairing = 'repairing';
    case Missing = 'missing';
    case Disposed = 'disposed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ready => 'Sẵn sàng trong kho',
            self::InEvent => 'Đang chạy sự kiện',
            self::InTransit => 'Đang vận chuyển',
            self::Repairing => 'Đang bảo dưỡng / Sửa chữa',
            self::Missing => 'Mất / Chưa trả về',
            self::Disposed => 'Đã thanh lý',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Ready => 'success',
            self::InEvent => 'info',
            self::InTransit => 'warning',
            self::Repairing => 'danger',
            self::Missing => 'danger',
            self::Disposed => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Ready => 'heroicon-o-check-circle',
            self::InEvent => 'heroicon-o-tv',
            self::InTransit => 'heroicon-o-truck',
            self::Repairing => 'heroicon-o-wrench-screwdriver',
            self::Missing => 'heroicon-o-exclamation-triangle',
            self::Disposed => 'heroicon-o-archive-box-x-mark',
        };
    }
}
