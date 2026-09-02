<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'quotation_id',
        'customer_id',
        'order_id',
        'title',
        'signed_date',
        'start_date',
        'end_date',
        'contract_value',
        'deposit_percent',
        'deposit_amount',
        'status',
        'terms',
        'note',
        'sales_user_id',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signed_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'contract_value' => 'decimal:2',
            'deposit_percent' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'status' => ContractStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Quotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function salesUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate total paid amount so far (từ đơn hàng liên kết)
     */
    public function getTotalPaidAttribute(): float
    {
        $order = $this->order;

        return $order ? (float) $order->total_paid : 0;
    }

    /**
     * Calculate remaining debt (so với đơn hàng liên kết)
     */
    public function getRemainingDebtAttribute(): float
    {
        $order = $this->order;

        if (! $order) {
            return max(0, (float) $this->contract_value);
        }

        return max(0, (float) $this->contract_value - (float) $order->total_paid);
    }
}
