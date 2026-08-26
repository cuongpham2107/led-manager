@if ($type === 'order')
    @php
        /** @var \App\Models\Order $order */
        $requestDate = $order->request_date ? \Carbon\Carbon::parse($order->request_date) : null;
        $returnDate = $order->expected_return_date ? \Carbon\Carbon::parse($order->expected_return_date) : null;
        $rentalDays = $order->rental_days ?: ($requestDate && $returnDate ? $requestDate->diffInDays($returnDate) + 1 : 1);
        
        $statusLabel = $order->status?->getLabel() ?? 'N/A';
        $statusBadgeClass = match ($order->status) {
            \App\Enums\OrderStatus::Completed => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800',
            \App\Enums\OrderStatus::OutboundCreated => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800',
            \App\Enums\OrderStatus::Dispatched => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-400 dark:border-sky-800',
            \App\Enums\OrderStatus::Returned => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800',
            \App\Enums\OrderStatus::Cancelled => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800',
            default => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
        };

        // Group milestones by planned date
        $milestonesByDate = $order->milestones->sortBy('planned_at')->groupBy(function ($m) {
            return $m->planned_at ? \Carbon\Carbon::parse($m->planned_at)->format('Y-m-d') : 'no_date';
        });
    @endphp

    <div class="space-y-4 text-left">
        <!-- ================= THÔNG TIN CHUNG ================= -->
        <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                        {{ $order->order_no }}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $statusBadgeClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>
                @if ($order->customer)
                    <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <svg class="size-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $order->customer->name }}</span>
                    </div>
                @endif
            </div>

            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                    {{ $order->event ?: 'Sự kiện chưa đặt tên' }}
                </h3>
            </div>

            <!-- Key Info Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1 text-xs">
                <div class="space-y-0.5">
                    <span class="text-gray-500 dark:text-gray-400">Thời gian thuê</span>
                    <p class="font-semibold text-gray-800 dark:text-gray-200">
                        {{ $requestDate ? $requestDate->format('d/m/Y') : '—' }} → {{ $returnDate ? $returnDate->format('d/m/Y') : 'Trong ngày' }}
                        <span class="text-gray-400 font-normal">({{ $rentalDays }} ngày)</span>
                    </p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-gray-500 dark:text-gray-400">Diện tích LED</span>
                    <p class="font-semibold text-gray-800 dark:text-gray-200">
                        {{ $order->area_m2 ? number_format((float) $order->area_m2, 1, ',', '.') . ' m²' : '—' }}
                    </p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-gray-500 dark:text-gray-400">Kho xuất hàng</span>
                    <p class="font-semibold text-gray-800 dark:text-gray-200 truncate">
                        {{ $order->warehouse?->name ?? '—' }}
                    </p>
                </div>

                <div class="space-y-0.5">
                    <span class="text-gray-500 dark:text-gray-400">Tổng giá trị</span>
                    <p class="font-bold text-primary-600 dark:text-primary-400">
                        {{ number_format((float) $order->value, 0, ',', '.') }} đ
                    </p>
                </div>
            </div>

            @if ($order->salesUser)
                <div class="text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                    Phụ trách kinh doanh: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $order->salesUser->name }}</span>
                </div>
            @endif
        </div>

        <!-- ================= PRELINE TIMELINE: TIẾN ĐỘ THI CÔNG ================= -->
        <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100 dark:border-gray-800">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                    <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tiến độ các mốc thi công
                </h4>
                <span class="text-xs text-gray-400">
                    {{ $order->milestones->count() }} mốc công việc
                </span>
            </div>

            @if ($order->milestones->isNotEmpty())
                <div class="w-full">
                    @foreach ($milestonesByDate as $dateKey => $milestones)
                        @php
                            $headingDate = $dateKey !== 'no_date' 
                                ? \Carbon\Carbon::parse($dateKey)->locale('vi')->isoFormat('dddd, DD/MM/YYYY') 
                                : 'Chưa định ngày';
                        @endphp

                        <!-- Heading Date -->
                        <div class="ps-2 my-2.5 first:mt-0">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ $headingDate }}
                            </h3>
                        </div>
                        <!-- End Heading -->

                        @foreach ($milestones as $ms)
                            @php
                                $msTime = $ms->planned_at ? \Carbon\Carbon::parse($ms->planned_at) : null;
                                $typeLabel = $ms->type->getLabel();
                                $statusLabel = $ms->status->getLabel();

                                $msStatusBadge = match ($ms->status) {
                                    \App\Enums\MilestoneStatus::Completed => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800',
                                    \App\Enums\MilestoneStatus::InProgress => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800',
                                    \App\Enums\MilestoneStatus::Skipped => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
                                };

                                $dotColor = match ($ms->status) {
                                    \App\Enums\MilestoneStatus::Completed => 'bg-emerald-500 border-emerald-200 dark:border-emerald-800',
                                    \App\Enums\MilestoneStatus::InProgress => 'bg-amber-500 border-amber-200 dark:border-amber-800',
                                    \App\Enums\MilestoneStatus::Skipped => 'bg-rose-500 border-rose-200 dark:border-rose-800',
                                    default => 'bg-gray-400 border-gray-200 dark:border-gray-700',
                                };
                            @endphp

                            <!-- Item -->
                            <div class="flex gap-x-3 relative group rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/40 px-2 py-1 transition">
                                <!-- Icon / Line -->
                                <div class="relative last:after:hidden after:absolute after:top-7 after:bottom-0 after:start-3.5 after:-translate-x-[0.5px] after:border-s after:border-gray-200 dark:after:border-gray-700">
                                    <div class="relative z-10 size-7 flex justify-center items-center">
                                        <div class="size-2 rounded-full {{ $dotColor }} border-2"></div>
                                    </div>
                                </div>
                                <!-- End Icon -->

                                <!-- Right Content -->
                                <div class="grow pt-0.5 pb-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h4 class="flex items-center gap-x-1.5 font-medium text-sm text-gray-900 dark:text-gray-100">
                                            <span>{{ $typeLabel }}</span>
                                        </h4>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $msStatusBadge }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    <div class="mt-1 flex items-center gap-x-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <svg class="size-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="font-mono">{{ $msTime ? $msTime->format('H:i') : '—' }}</span>
                                    </div>

                                    @if ($ms->note)
                                        <p class="mt-1.5 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/60 p-2 rounded-md border border-gray-100 dark:border-gray-800">
                                            📝 {{ $ms->note }}
                                        </p>
                                    @endif
                                </div>
                                <!-- End Right Content -->
                            </div>
                            <!-- End Item -->
                        @endforeach
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-xs text-gray-400 dark:text-gray-500 italic">
                    Chưa tạo các mốc thi công chi tiết cho đơn hàng này.
                </div>
            @endif
        </div>

        <!-- ================= GHI CHÚ ĐƠN HÀNG ================= -->
        @if ($order->note)
            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300">
                <span class="font-semibold text-gray-700 dark:text-gray-200">Ghi chú:</span> {{ $order->note }}
            </div>
        @endif
    </div>

@elseif ($type === 'milestone')
    @php
        /** @var \App\Models\EventMilestone $milestone */
        $ms = $milestone;
        $msTime = $ms->planned_at ? \Carbon\Carbon::parse($ms->planned_at) : null;
        $typeLabel = $ms->type->getLabel();
        $statusLabel = $ms->status->getLabel();

        $statusBadge = match ($ms->status) {
            \App\Enums\MilestoneStatus::Completed => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800',
            \App\Enums\MilestoneStatus::InProgress => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800',
            \App\Enums\MilestoneStatus::Skipped => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800',
            default => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
        };
    @endphp

    <div class="space-y-3 text-left">
        <!-- Card Thông tin mốc -->
        <div class="p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 space-y-3">
            <div class="flex items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Mốc lịch trình thi công
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $statusBadge }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                {{ $typeLabel }}
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 text-xs">
                <div class="space-y-0.5">
                    <span class="text-gray-500 dark:text-gray-400">Thời gian dự kiến</span>
                    <p class="font-semibold text-gray-800 dark:text-gray-200">
                        {{ $msTime ? $msTime->format('H:i - d/m/Y (l)') : '—' }}
                    </p>
                </div>

                @if ($ms->order)
                    <div class="space-y-0.5">
                        <span class="text-gray-500 dark:text-gray-400">Thuộc đơn hàng</span>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">
                            [{{ $ms->order->order_no }}] {{ $ms->order->event }}
                        </p>
                    </div>
                @endif
            </div>

            @if ($ms->order?->customer)
                <div class="text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                    Khách hàng: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $ms->order->customer->name }}</span>
                </div>
            @endif
        </div>

        @if ($ms->note)
            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300">
                <span class="font-semibold text-gray-700 dark:text-gray-200">Ghi chú mốc:</span> {{ $ms->note }}
            </div>
        @endif
    </div>
@endif
