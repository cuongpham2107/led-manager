<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RepairResultStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Fixed = 'fixed';
    case Disposed = 'disposed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Đang chờ xử lý / Đang sửa',
            self::Fixed => 'Đã sửa xong (Sẵn sàng)',
            self::Disposed => 'Hỏng nặng không thể sửa (Thanh lý)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Fixed => 'success',
            self::Disposed => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Fixed => 'heroicon-o-check-circle',
            self::Disposed => 'heroicon-o-archive-box-x-mark',
        };
    }
}
