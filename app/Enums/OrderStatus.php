<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case OutboundCreated = 'outbound_created';
    case Dispatched = 'dispatched';
    case Returned = 'returned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Mới tạo (Draft)',
            self::OutboundCreated => 'Đã tạo đợt xuất kho',
            self::Dispatched => 'Đã xuất kho đi sự kiện',
            self::Returned => 'Đã thu hồi trả kho',
            self::Completed => 'Hoàn tất đơn hàng',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::OutboundCreated => 'warning',
            self::Dispatched => 'info',
            self::Returned => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-document-plus',
            self::OutboundCreated => 'heroicon-o-clipboard-document-check',
            self::Dispatched => 'heroicon-o-truck',
            self::Returned => 'heroicon-o-arrow-path-rounded-square',
            self::Completed => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-no-symbol',
        };
    }
}
