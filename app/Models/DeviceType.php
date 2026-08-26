<?php

namespace App\Models;

use App\Enums\DeviceUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'unit',
        'requires_serial',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => DeviceUnit::class,
            'requires_serial' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * @return HasMany<QuotationItem, $this>
     */
    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<CheckoutBatch, $this>
     */
    public function checkoutBatches(): HasMany
    {
        return $this->hasMany(CheckoutBatch::class);
    }
}
