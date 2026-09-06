@props([
    'history' => collect(),
    'repairLogs' => collect(),
    'record' => null,
])

@php
    $operatingHours = (int) ($record?->operating_hours ?? 0);
    $rentalCount = (int) ($record?->rental_count ?? 0);
    $eventsCount = $history->count();
    $repairCount = $repairLogs->count();
    $normalReturns = $history->filter(fn ($i) => $i->returnBatchItem?->grade?->value === 'normal')->count();
    $damagedReturns = $history->filter(fn ($i) => $i->returnBatchItem?->grade?->value === 'damaged')->count();
@endphp

<div
    x-data="{ activeTab: 'rentals' }"
    class="space-y-4 text-gray-900 dark:text-gray-100"
>
    {{-- Metric Overview Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="p-3.5 rounded-xl bg-primary-50/60 dark:bg-primary-950/30 border border-primary-100 dark:border-primary-900/50 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary-600/10 dark:bg-primary-400/10 text-primary-600 dark:text-primary-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Số giờ chạy</p>
                <p class="text-lg font-bold text-primary-700 dark:text-primary-300">
                    {{ number_format($operatingHours, 0, ',', '.') }} <span class="text-xs font-semibold">h</span>
                </p>
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-600/10 dark:bg-emerald-400/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Số lần cho thuê</p>
                <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">
                    {{ number_format($rentalCount, 0, ',', '.') }} <span class="text-xs font-semibold">lần</span>
                </p>
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-600/10 dark:bg-amber-400/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Đợt đi sự kiện</p>
                <p class="text-lg font-bold text-amber-700 dark:text-amber-300">
                    {{ $eventsCount }} <span class="text-xs font-semibold">đợt</span>
                </p>
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-purple-50/60 dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/50 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-600/10 dark:bg-purple-400/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Bảo trì / Sửa chữa</p>
                <p class="text-lg font-bold text-purple-700 dark:text-purple-300">
                    {{ $repairCount }} <span class="text-xs font-semibold">lượt</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-2">
        <button
            type="button"
            @click="activeTab = 'rentals'"
            :class="activeTab === 'rentals'
                ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 border-primary-200 dark:border-primary-800 font-semibold'
                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800/50'"
            class="px-3.5 py-1.5 text-xs rounded-lg border transition-all inline-flex items-center gap-2 cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <span>Lịch sử cho thuê & sự kiện ({{ $eventsCount }})</span>
        </button>

        <button
            type="button"
            @click="activeTab = 'repairs'"
            :class="activeTab === 'repairs'
                ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 border-primary-200 dark:border-primary-800 font-semibold'
                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 border-transparent hover:bg-gray-50 dark:hover:bg-gray-800/50'"
            class="px-3.5 py-1.5 text-xs rounded-lg border transition-all inline-flex items-center gap-2 cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <span>Lịch sử sửa chữa & bảo dưỡng ({{ $repairCount }})</span>
        </button>
    </div>

    {{-- Tab 1: Rentals / Checkout History --}}
    <div x-show="activeTab === 'rentals'" class="space-y-2">
        @if ($history->isEmpty())
            <div class="py-8 text-center border border-dashed border-gray-200 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-gray-900/50">
                <svg class="w-10 h-10 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Chưa có lịch sử làm việc</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Thiết bị chưa từng được xuất kho điều động cho đợt sự kiện nào.</p>
            </div>
        @else
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-xs">
                <div class="max-h-80 overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="sticky top-0 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-xs text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider z-1 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-3.5 py-2.5">Đơn hàng & Sự kiện</th>
                                <th class="px-3.5 py-2.5">Đợt xuất kho</th>
                                <th class="px-3.5 py-2.5">Khách hàng</th>
                                <th class="px-3.5 py-2.5">Thời gian xuất / Hoàn trả</th>
                                <th class="px-3.5 py-2.5 text-center">Tình trạng hoàn trả</th>
                                <th class="px-3.5 py-2.5">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($history as $item)
                                @php
                                    $batch = $item->checkoutBatch;
                                    $order = $batch?->order;
                                    $customer = $order?->customer ?? $batch?->customer;
                                    $returnItem = $item->returnBatchItem;
                                    $dispatchedAt = $item->dispatched_at ?? $batch?->dispatched_at;
                                    $receivedAt = $returnItem?->received_at;
                                    $isReturned = (bool) ($returnItem?->is_received);
                                @endphp
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-3.5 py-2.5 align-top">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $order?->order_no ?? '—' }}
                                        </div>
                                        @if ($order?->event)
                                            <div class="text-[11px] text-primary-600 dark:text-primary-400 font-medium mt-0.5 line-clamp-1" title="{{ $order->event }}">
                                                {{ $order->event }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-3.5 py-2.5 align-top">
                                        <div class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $batch?->code ?? '—' }}
                                        </div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">
                                            {{ $batch?->warehouse?->name ?? 'Kho gốc' }}
                                        </div>
                                    </td>

                                    <td class="px-3.5 py-2.5 align-top">
                                        <div class="text-gray-900 dark:text-gray-200 font-medium max-w-[180px] truncate" title="{{ $customer?->name ?? '—' }}">
                                            {{ $customer?->name ?? '—' }}
                                        </div>
                                        @if ($customer?->phone)
                                            <div class="text-[11px] text-gray-400 mt-0.5">
                                                {{ $customer->phone }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-3.5 py-2.5 align-top">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                Xuất: <strong class="text-gray-900 dark:text-gray-100 font-medium">{{ $dispatchedAt ? $dispatchedAt->format('d/m/Y H:i') : ($batch?->created_at?->format('d/m/Y') ?? '—') }}</strong>
                                            </span>
                                            @if ($isReturned && $receivedAt)
                                                <span class="text-emerald-600 dark:text-emerald-400">
                                                    Trả: <strong class="font-medium">{{ $receivedAt->format('d/m/Y H:i') }}</strong>
                                                </span>
                                            @elseif ($batch?->expected_return_date)
                                                <span class="text-amber-600 dark:text-amber-400 text-[11px]">
                                                    Dự kiến: {{ $batch->expected_return_date->format('d/m/Y') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-3.5 py-2.5 align-top text-center">
                                        @if ($isReturned && $returnItem)
                                            @if ($returnItem->grade?->value === 'normal')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                    <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                    Đạt chuẩn
                                                </span>
                                            @elseif ($returnItem->grade?->value === 'damaged')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                                    <svg class="w-3 h-3 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                    Lỗi / Hỏng
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                    Đã nhập kho
                                                </span>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                Đang cho thuê
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-3.5 py-2.5 align-top text-gray-500 dark:text-gray-400 max-w-[150px] truncate" title="{{ $returnItem?->grade_note ?: ($item->note ?: '—') }}">
                                        {{ $returnItem?->grade_note ?: ($item->note ?: '—') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- Tab 2: Maintenance & Repairs History --}}
    <div x-show="activeTab === 'repairs'" style="display: none;" class="space-y-2">
        @if ($repairLogs->isEmpty())
            <div class="py-8 text-center border border-dashed border-gray-200 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-gray-900/50">
                <svg class="w-10 h-10 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Không có lịch sử sửa chữa</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Thiết bị chưa từng phải sửa chữa hoặc bảo dưỡng ghi nhận trong hệ thống.</p>
            </div>
        @else
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-xs">
                <div class="max-h-80 overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="sticky top-0 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-xs text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider z-1 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-3.5 py-2.5">Thời gian sửa chữa</th>
                                <th class="px-3.5 py-2.5">Kết quả</th>
                                <th class="px-3.5 py-2.5">Chi phí sửa chữa</th>
                                <th class="px-3.5 py-2.5">Nội dung / Ghi chú</th>
                                <th class="px-3.5 py-2.5">Người phụ trách</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($repairLogs as $log)
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-3.5 py-2.5 align-top">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $log->start_date ? $log->start_date->format('d/m/Y') : '—' }}
                                        </div>
                                        <div class="text-[11px] text-gray-400">
                                            Đến: {{ $log->end_date ? $log->end_date->format('d/m/Y') : 'Đang sửa chữa' }}
                                        </div>
                                    </td>

                                    <td class="px-3.5 py-2.5 align-top">
                                        @php
                                            $resultStatus = $log->result_status;
                                            $resultLabel = $resultStatus instanceof \App\Enums\RepairResultStatus ? $resultStatus->getLabel() : (string) ($resultStatus?->value ?? $resultStatus);
                                            $badgeColor = match ($resultStatus?->value ?? (string) $resultStatus) {
                                                'fixed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                                                'cannot_repair' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                                                'disposed' => 'bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700',
                                                default => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeColor }}">
                                            {{ $resultLabel }}
                                        </span>
                                    </td>

                                    <td class="px-3.5 py-2.5 align-top font-semibold text-gray-900 dark:text-gray-100">
                                        {{ number_format((float) ($log->repair_cost ?? 0), 0, ',', '.') }} đ
                                    </td>

                                    <td class="px-3.5 py-2.5 align-top text-gray-700 dark:text-gray-300">
                                        {{ $log->repair_note ?: '—' }}
                                    </td>

                                    <td class="px-3.5 py-2.5 align-top text-gray-500 dark:text-gray-400">
                                        {{ $log->creator?->name ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
