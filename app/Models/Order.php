<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $order_no
 * @property string|null $note
 * @property int $warehouse_id
 * @property int $customer_id
 * @property int|null $quotation_id
 * @property Carbon|null $request_date
 * @property Carbon|null $expected_return_date
 * @property string|null $area_m2
 * @property string|null $event
 * @property int|null $device_type_id
 * @property string $value
 * @property OrderStatus $status
 * @property int|null $sales_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Warehouse|null $warehouse
 * @property-read Customer|null $customer
 * @property-read Quotation|null $quotation
 * @property-read DeviceType|null $deviceType
 * @property-read User|null $salesUser
 */
class Order extends Model implements Eventable
{
    use HasFactory;

    protected $fillable = [
        'order_no',
        'note',
        'warehouse_id',
        'customer_id',
        'quotation_id',
        'request_date',
        'expected_return_date',
        'area_m2',
        'event',
        'device_type_id',
        'value',
        'status',
        'sales_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'expected_return_date' => 'date',
            'area_m2' => 'decimal:2',
            'value' => 'decimal:2',
            'status' => OrderStatus::class,
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
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Quotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * @return BelongsTo<DeviceType, $this>
     */
    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function salesUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<CheckoutBatch, $this>
     */
    public function checkoutBatches(): HasMany
    {
        return $this->hasMany(CheckoutBatch::class);
    }

    /**
     * @return HasOne<Quotation, $this>
     */
    public function convertedFromQuotation(): HasOne
    {
        return $this->hasOne(Quotation::class, 'converted_order_id');
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<EventAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EventAssignment::class);
    }

    /**
     * @return HasMany<EventMilestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(EventMilestone::class);
    }

    /**
     * Get deposit amount paid so far
     */
    public function getDepositPaidAttribute(): float
    {
        $orderDeposit = (float) $this->payments->where('type', PaymentType::Deposit)->sum('amount');
        $contractDeposit = (float) $this->contracts->flatMap->payments->where('type', PaymentType::Deposit)->sum('amount');

        return max($orderDeposit, $contractDeposit);
    }

    /**
     * Get total amount paid so far
     */
    public function getTotalPaidAttribute(): float
    {
        $orderPaid = (float) $this->payments->where('type', '!=', PaymentType::Refund)->sum('amount');
        $contractPaid = (float) $this->contracts->flatMap->payments->where('type', '!=', PaymentType::Refund)->sum('amount');

        return max($orderPaid, $contractPaid);
    }

    public function toCalendarEvent(): CalendarEvent
    {
        $startDate = $this->request_date?->toDateString() ?? now()->toDateString();
        $targetDate = $this->expected_return_date ?? $this->request_date;
        $endDate = $targetDate ? $targetDate->copy()->addDay()->toDateString() : $startDate;

        $bgColor = match ($this->status) {
            OrderStatus::Completed => '#10b981',
            OrderStatus::OutboundCreated, OrderStatus::Dispatched => '#0284c7',
            OrderStatus::Returned => '#8b5cf6',
            OrderStatus::Draft => '#f59e0b',
            default => '#64748b',
        };

        return CalendarEvent::make($this)
            ->title("📅 [{$this->order_no}] {$this->event} - ".($this->customer?->name ?? ''))
            ->start($startDate)
            ->end($endDate)
            ->allDay(true)
            ->backgroundColor($bgColor)
            ->textColor('#ffffff');
    }
}
