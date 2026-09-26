<?php

namespace App\Models;

use App\Enums\LedScanMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cấu hình kỹ thuật của tấm LED (card nhận, kiểu quét, đầu phát tương thích).
 * Các tấm cùng dòng sản phẩm phải cùng cấu hình mới lắp chung một màn.
 */
class LedConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_line_id',
        'name',
        'receiving_card',
        'scan_mode',
        'controller_model',
        'note',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scan_mode' => LedScanMode::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ProductLine, $this>
     */
    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function getLabelAttribute(): string
    {
        return collect([
            $this->name,
            $this->receiving_card,
            $this->scan_mode?->getLabel(),
            $this->controller_model,
        ])->filter()->implode(' · ');
    }
}
