<?php

namespace App\Filament\Widgets;

use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\EventMilestone;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class EventCalendarWidget extends FullCalendarWidget
{
    public static int $gridW = 24;

    public static int $gridH = 14;

    protected static ?int $sort = 5;

    public function fetchEvents(array $info): array
    {
        $events = [];

        // 1. Orders (Sự kiện cho thuê màn hình LED)
        $orderQuery = Order::query()
            ->with(['customer', 'warehouse', 'salesUser', 'milestones'])
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereDate('request_date', '<=', $info['end'])
            ->where(function ($q) use ($info) {
                $q->whereNull('expected_return_date')
                    ->orWhereDate('expected_return_date', '>=', $info['start']);
            });

        if ($whId = auth()->user()?->getScopedWarehouseId()) {
            $orderQuery->where('warehouse_id', $whId);
        }

        $orders = $orderQuery->get();

        foreach ($orders as $order) {
            $startDate = $order->request_date?->toDateString() ?? now()->toDateString();
            $endDate = $order->expected_return_date
                ? $order->expected_return_date->copy()->addDay()->toDateString()
                : $order->request_date?->copy()->addDay()->toDateString();

            $bgColor = match ($order->status) {
                OrderStatus::Completed => '#10b981', // emerald
                OrderStatus::OutboundCreated, OrderStatus::Dispatched => '#0284c7', // sky
                OrderStatus::Returned => '#8b5cf6', // violet
                OrderStatus::Draft => '#f59e0b', // amber
                default => '#64748b', // slate
            };

            $events[] = EventData::make()
                ->id('order_'.$order->id)
                ->title("📅 [{$order->order_no}] {$order->event} - ".($order->customer?->name ?? ''))
                ->start($startDate)
                ->end($endDate)
                ->allDay(true)
                ->backgroundColor($bgColor)
                ->borderColor($bgColor)
                ->textColor('#ffffff')
                ->toArray();
        }

        // 2. Event Milestones (Các mốc lịch trình thi công, lắp đặt, chạy thử)
        $milestones = EventMilestone::query()
            ->with(['order.customer'])
            ->whereDate('planned_at', '>=', $info['start'])
            ->whereDate('planned_at', '<=', $info['end'])
            ->get();

        foreach ($milestones as $ms) {
            $start = $ms->planned_at?->toIso8601String() ?? now()->toIso8601String();
            $end = $ms->planned_at?->copy()->addHours(2)->toIso8601String();

            $msColor = match ($ms->type) {
                MilestoneType::Delivery => '#06b6d4',
                MilestoneType::Setup => '#8b5cf6',
                MilestoneType::Testing => '#6366f1',
                MilestoneType::EventStart => '#10b981',
                MilestoneType::EventEnd => '#64748b',
                MilestoneType::Teardown => '#ea580c',
                MilestoneType::ReturnToWarehouse => '#059669',
            };

            $events[] = EventData::make()
                ->id('milestone_'.$ms->id)
                ->title("⏱️ [{$ms->type->getLabel()}] {$ms->order?->event}")
                ->start($start)
                ->end($end)
                ->allDay(false)
                ->backgroundColor($msColor)
                ->borderColor($msColor)
                ->textColor('#ffffff')
                ->toArray();
        }

        return $events;
    }

    protected function headerActions(): array
    {
        return [
            Action::make('createOrder')
                ->label('Tạo Đơn Hàng Mới')
                ->icon(Heroicon::OutlinedPlus)
                ->color('primary')
                ->url(OrderResource::getUrl('create')),
            Action::make('createMilestone')
                ->label('Thêm Mốc Thi Công')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->modalHeading('Thêm Mốc Lịch Trình Thi Công Sự Kiện')
                ->schema([
                    Select::make('order_id')
                        ->label('Đơn hàng sự kiện')
                        ->options(fn () => Order::pluck('order_no', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('type')
                        ->label('Loại mốc thi công')
                        ->options(MilestoneType::class)
                        ->required(),
                    DateTimePicker::make('planned_at')
                        ->label('Thời gian dự kiến')
                        ->default(now())
                        ->required(),
                    Select::make('status')
                        ->label('Trạng thái')
                        ->options(MilestoneStatus::class)
                        ->default(MilestoneStatus::Pending)
                        ->required(),
                    Textarea::make('note')
                        ->label('Ghi chú'),
                ])
                ->action(function (array $data): void {
                    EventMilestone::create($data);
                    Notification::make()
                        ->title('Thành công')
                        ->body('Đã thêm mốc thi công vào lịch trình!')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function viewAction(): Action
    {
        return Action::make('view')
            ->modalHeading(function (array $arguments) {
                $idStr = $arguments['event']['id'] ?? '';
                if (str_starts_with($idStr, 'order_')) {
                    $orderId = (int) str_replace('order_', '', $idStr);
                    $order = Order::find($orderId);

                    return $order ? "Chi tiết Đơn hàng [{$order->order_no}]" : 'Chi tiết Sự kiện';
                }
                if (str_starts_with($idStr, 'milestone_')) {
                    return 'Chi tiết Mốc thi công';
                }

                return 'Chi tiết Sự kiện';
            })
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Đóng')
            ->extraModalFooterActions(function (array $arguments): array {
                $idStr = $arguments['event']['id'] ?? '';
                if (str_starts_with($idStr, 'order_')) {
                    $orderId = (int) str_replace('order_', '', $idStr);
                    $order = Order::find($orderId);
                    if (! $order) {
                        return [];
                    }

                    return [
                        Action::make('editOrder')
                            ->label('Xem & Chỉnh sửa đơn hàng')
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->color('primary')
                            ->url(OrderResource::getUrl('edit', ['record' => $order])),
                    ];
                }

                if (str_starts_with($idStr, 'milestone_')) {
                    $msId = (int) str_replace('milestone_', '', $idStr);
                    $ms = EventMilestone::with('order')->find($msId);
                    if (! $ms?->order) {
                        return [];
                    }

                    return [
                        Action::make('viewOrder')
                            ->label('Xem Đơn hàng liên quan')
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->color('primary')
                            ->url(OrderResource::getUrl('edit', ['record' => $ms->order])),
                    ];
                }

                return [];
            })
            ->schema(function (array $arguments): array {
                $idStr = $arguments['event']['id'] ?? '';
                if (str_starts_with($idStr, 'order_')) {
                    $orderId = (int) str_replace('order_', '', $idStr);
                    $order = Order::with(['customer', 'warehouse', 'salesUser', 'milestones'])->find($orderId);
                    if (! $order) {
                        return [TextEntry::make('err')->state('Không tìm thấy thông tin đơn hàng.')];
                    }

                    return [
                        TextEntry::make('order_modal_content')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->state(new HtmlString(
                                view('filament.components.calendar-event-modal', [
                                    'type' => 'order',
                                    'order' => $order,
                                ])->render()
                            )),
                    ];
                }

                if (str_starts_with($idStr, 'milestone_')) {
                    $msId = (int) str_replace('milestone_', '', $idStr);
                    $ms = EventMilestone::with(['order.customer'])->find($msId);
                    if (! $ms) {
                        return [TextEntry::make('err')->state('Không tìm thấy thông tin mốc thi công.')];
                    }

                    return [
                        TextEntry::make('milestone_modal_content')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->state(new HtmlString(
                                view('filament.components.calendar-event-modal', [
                                    'type' => 'milestone',
                                    'milestone' => $ms,
                                ])->render()
                            )),
                    ];
                }

                return [];
            });
    }

    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        $idStr = $event['id'] ?? '';
        $deltaDays = (int) ($delta['days'] ?? 0);

        if (str_starts_with($idStr, 'order_')) {
            $orderId = (int) str_replace('order_', '', $idStr);
            $order = Order::find($orderId);
            if ($order && $deltaDays !== 0) {
                if ($order->request_date) {
                    $order->request_date = Carbon::parse($order->request_date)->addDays($deltaDays);
                }
                if ($order->expected_return_date) {
                    $order->expected_return_date = Carbon::parse($order->expected_return_date)->addDays($deltaDays);
                }
                $order->save();

                Notification::make()
                    ->title('Cập nhật lịch trình')
                    ->body("Đã dời ngày đơn hàng {$order->order_no} sang {$order->request_date->format('d/m/Y')}")
                    ->success()
                    ->send();
            }
        } elseif (str_starts_with($idStr, 'milestone_')) {
            $msId = (int) str_replace('milestone_', '', $idStr);
            $ms = EventMilestone::find($msId);
            if ($ms && $ms->planned_at && $deltaDays !== 0) {
                $ms->planned_at = Carbon::parse($ms->planned_at)->addDays($deltaDays);
                $ms->save();

                Notification::make()
                    ->title('Cập nhật mốc thi công')
                    ->body("Đã dời mốc {$ms->type->getLabel()} sang {$ms->planned_at->format('H:i d/m/Y')}")
                    ->success()
                    ->send();
            }
        }

        return false;
    }

    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'height' => 'calc(100vh - 220px)',
            'expandRows' => true,
            'dayMaxEvents' => 3,
            'stickyHeaderDates' => true,
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
            ],
            'buttonText' => [
                'today' => 'Hôm nay',
                'month' => 'Tháng',
                'week' => 'Tuần',
                'day' => 'Ngày',
                'list' => 'Danh sách',
            ],
            'navLinks' => true,
            'nowIndicator' => true,
            'editable' => true,
            'selectable' => true,
        ];
    }
}
