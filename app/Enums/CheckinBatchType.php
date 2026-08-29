<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CheckinBatchType: string implements HasColor, HasIcon, HasLabel
{
    case Production = 'production';
    case Purchase = 'purchase';
    case Transfer = 'transfer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Production => 'Nhập từ sản xuất',
            self::Purchase => 'Mua ngoài',
            self::Transfer => 'Chuyển kho nội bộ',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Production => 'success',
            self::Purchase => 'info',
            self::Transfer => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Production => 'heroicon-m-cog-6-tooth',
            self::Purchase => 'heroicon-m-shopping-cart',
            self::Transfer => 'heroicon-m-arrows-right-left',
        };
    }
}
