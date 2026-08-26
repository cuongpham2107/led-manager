<?php

namespace App\Models;

use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property MilestoneType $type
 * @property Carbon|null $planned_at
 * @property Carbon|null $actual_at
 * @property MilestoneStatus $status
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order|null $order
 */
class EventMilestone extends Model implements Eventable
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'planned_at',
        'actual_at',
        'status',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MilestoneType::class,
            'planned_at' => 'datetime',
            'actual_at' => 'datetime',
            'status' => MilestoneStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function toCalendarEvent(): CalendarEvent
    {
        $start = $this->planned_at ?? now();
        $end = $this->planned_at ? $this->planned_at->copy()->addHours(2) : now()->addHours(2);

        $msColor = match ($this->type) {
            MilestoneType::Delivery => '#06b6d4',
            MilestoneType::Setup => '#8b5cf6',
            MilestoneType::Testing => '#6366f1',
            MilestoneType::EventStart => '#10b981',
            MilestoneType::EventEnd => '#64748b',
            MilestoneType::Teardown => '#ea580c',
            MilestoneType::ReturnToWarehouse => '#059669',
        };

        return CalendarEvent::make($this)
            ->title("⏱️ [{$this->type->getLabel()}] {$this->order?->event}")
            ->start($start)
            ->end($end)
            ->allDay(false)
            ->backgroundColor($msColor)
            ->textColor('#ffffff');
    }
}
