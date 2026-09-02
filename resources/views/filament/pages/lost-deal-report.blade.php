<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $reasons = $this->getReasonBreakdown();
    @endphp

    <div class="space-y-6">
        {{-- TOP STAT CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Tổng số Deal thất thoát -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Số
                            Deal Thất Thoát</p>
                        <h3 class="text-3xl font-black text-red-600 dark:text-red-400 mt-1">
                            {{ number_format($stats['total_deals']) }} <span
                                class="text-xs font-normal text-gray-400">báo giá</span></h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-red-50 dark:bg-red-950/50 flex items-center justify-center text-red-600 dark:text-red-400">
                        <x-heroicon-o-x-circle class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300">
                        Khách từ chối / Hết hạn
                    </span>
                </div>
            </div>

            <!-- Giá trị thất thoát -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Giá
                            Trị Doanh Thu Hụt</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-white mt-1">
                            {{ number_format($stats['total_value']) }} <span
                                class="text-xs font-normal text-gray-400">VND</span></h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-currency-dollar class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span>Tổng tiền các báo giá không thành</span>
                </div>
            </div>

            <!-- Diện tích LED thất thoát -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tổng
                            Diện Tích Màn Hụt</p>
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white mt-1">
                            {{ number_format($stats['total_area'], 1) }} <span
                                class="text-xs font-normal text-gray-400">m²</span></h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-950/50 flex items-center justify-center text-purple-600 dark:text-purple-400">
                        <x-heroicon-o-squares-2x2 class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span>Quy đổi diện tích cabinet</span>
                </div>
            </div>

            <!-- Nguyên nhân hàng đầu -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Nguyên Nhân Chiếm Đa Số</p>
                        <h3 class="text-sm font-bold text-red-600 dark:text-red-400 line-clamp-2 mt-1">
                            {{ $stats['primary_reason'] }}</h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span>Cần tối ưu chính sách theo lý do này</span>
                </div>
            </div>
        </div>

        {{-- VISUAL ROOT CAUSE BREAKDOWN CARDS --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-chart-pie class="w-5 h-5 text-red-500" />
                        Cơ Cấu Phân Bổ Các Nguyên Nhân Thất Bại
                    </h3>
                    <p class="text-xs text-gray-500">Thống kê chi tiết tỷ lệ % và số tiền thiệt hại theo từng nhóm lý do
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($reasons as $r)
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between hover:border-red-400 transition-all">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-2">{{ $r['reason'] }}
                                </h4>
                                <span
                                    class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300 shrink-0">
                                    {{ $r['pct'] }}%
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 mt-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Số vụ mất</span>
                                    <p class="text-base font-black text-gray-900 dark:text-white mt-0.5">{{ $r['count'] }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-red-500">Giá trị hụt</span>
                                    <p class="text-sm font-black text-red-600 dark:text-red-400 mt-0.5">
                                        {{ number_format($r['value']) }} đ</p>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-linear-to-r from-red-500 to-rose-400 h-2 rounded-full"
                                    style="width: {{ $r['pct'] }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center text-gray-400 italic">
                        Tuyệt vời! Hiện tại không có báo giá nào bị từ chối hoặc thất thoát.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- DETAILED LOST DEALS TABLE --}}
        <div class="mt-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-table-cells class="w-5 h-5 text-gray-500" />
                    Bảng Kê Chi Tiết Danh Sách Báo Giá Thất Thoát
                </h3>
            </div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>