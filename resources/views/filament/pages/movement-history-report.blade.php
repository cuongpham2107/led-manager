<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <div class="space-y-6">
        {{-- KPI STATS OVERVIEW --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Tổng lượt biến động -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Tổng Biến Động Thiết Bị
                        </p>
                        <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">
                            {{ number_format($stats['total']) }}
                            <span class="text-xs font-normal text-gray-400">giao dịch</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-950/50 flex items-center justify-center text-primary-600 dark:text-primary-400">
                        <x-heroicon-o-arrows-right-left class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-primary-600 dark:text-primary-400">
                        {{ number_format($stats['last_30_days']) }} giao dịch
                    </span>
                    <span>trong 30 ngày qua</span>
                </div>
            </div>

            <!-- Lượt nhập kho -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Lượt Nhập Kho
                        </p>
                        <h3 class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ number_format($stats['checkins']) }}
                            <span class="text-xs font-normal text-gray-400">lượt</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-arrow-down-on-square class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                        Nhập mới & Tiếp nhận
                    </span>
                </div>
            </div>

            <!-- Lượt xuất kho sự kiện -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Lượt Xuất Sự Kiện
                        </p>
                        <h3 class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">
                            {{ number_format($stats['checkouts']) }}
                            <span class="text-xs font-normal text-gray-400">lượt</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <x-heroicon-o-arrow-up-tray class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                        Xuất chạy chương trình
                    </span>
                </div>
            </div>

            <!-- Thu hồi & Bảo dưỡng -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Thu Hồi & Bảo Dưỡng
                        </p>
                        <h3 class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">
                            {{ number_format($stats['returns'] + $stats['repairs']) }}
                            <span class="text-xs font-normal text-gray-400">lượt</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-arrow-path class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Thu hồi: <strong>{{ number_format($stats['returns']) }}</strong></span>
                    <span>Bảo dưỡng: <strong>{{ number_format($stats['repairs']) }}</strong></span>
                </div>
            </div>
        </div>

        {{-- BẢNG NHẬT KÝ LỊCH SỬ --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
                    Nhật Ký Lịch Sử Nhập / Xuất Kho
                </h3>
            </div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
