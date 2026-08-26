<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum QuotationStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Converted = 'converted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Dự thảo (Nháp)',
            self::Sent => 'Đã gửi khách',
            self::Approved => 'Khách duyệt',
            self::Converted => 'Đã chốt (Tạo đơn)',
            self::Rejected => 'Bị từ chối',
            self::Expired => 'Hết hạn',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Approved => 'warning',
            self::Converted => 'success',
            self::Rejected => 'danger',
            self::Expired => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::Sent => 'heroicon-o-paper-airplane',
            self::Approved => 'heroicon-o-hand-thumb-up',
            self::Converted => 'heroicon-o-check-badge',
            self::Rejected => 'heroicon-o-x-circle',
            self::Expired => 'heroicon-o-clock',
        };
    }
}
