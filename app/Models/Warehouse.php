<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'current_warehouse_id');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<CheckinBatch, $this>
     */
    public function checkinBatches(): HasMany
    {
        return $this->hasMany(CheckinBatch::class);
    }

    /**
     * @return HasMany<CheckoutBatch, $this>
     */
    public function checkoutBatches(): HasMany
    {
        return $this->hasMany(CheckoutBatch::class);
    }

    /**
     * @return HasMany<AssetStatusLog, $this>
     */
    public function fromStatusLogs(): HasMany
    {
        return $this->hasMany(AssetStatusLog::class, 'from_warehouse_id');
    }

    /**
     * @return HasMany<AssetStatusLog, $this>
     */
    public function toStatusLogs(): HasMany
    {
        return $this->hasMany(AssetStatusLog::class, 'to_warehouse_id');
    }
}
