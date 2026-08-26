<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\EventMilestone;
use App\Models\Order;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GuavaEventCalendarWidget extends CalendarWidget
{
    public static int $gridW = 24;

    public static int $gridH = 14;

    protected static ?int $sort = 5;

    protected string|\Illuminate\Support\HtmlString|null|bool $heading = 'Lịch trình Dự án & Lắp đặt Màn hình LED';

    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        $orders = Order::query()
            ->with('customer')
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereDate('request_date', '<=', $info->end)
            ->where(function ($q) use ($info) {
                $q->whereNull('expected_return_date')
                    ->orWhereDate('expected_return_date', '>=', $info->start);
            })
            ->get();

        $milestones = EventMilestone::query()
            ->with(['order.customer'])
            ->whereDate('planned_at', '>=', $info->start)
            ->whereDate('planned_at', '<=', $info->end)
            ->get();

        return collect()
            ->push(...$orders)
            ->push(...$milestones);
    }
}
