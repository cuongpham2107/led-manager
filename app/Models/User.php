<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin HasRoles
 */
#[Fillable(['name', 'email', 'password', 'phone', 'warehouse_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<Quotation, $this>
     */
    public function salesQuotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'sales_user_id');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'sales_user_id');
    }

    /**
     * @return HasMany<CheckinBatch, $this>
     */
    public function createdCheckinBatches(): HasMany
    {
        return $this->hasMany(CheckinBatch::class, 'created_by');
    }

    /**
     * @return HasMany<CheckoutBatch, $this>
     */
    public function createdCheckoutBatches(): HasMany
    {
        return $this->hasMany(CheckoutBatch::class, 'created_by');
    }

    /**
     * @return HasMany<ReturnBatch, $this>
     */
    public function createdReturnBatches(): HasMany
    {
        return $this->hasMany(ReturnBatch::class, 'created_by');
    }

    /**
     * @return HasMany<RepairLog, $this>
     */
    public function repairLogs(): HasMany
    {
        return $this->hasMany(RepairLog::class, 'created_by');
    }
}
