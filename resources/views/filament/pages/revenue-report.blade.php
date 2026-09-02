<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $topProjects = $this->getTopProjects();
    @endphp

    <div class="space-y-6">
        {{-- KPI STATS CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Tổng Doanh Thu -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tổng
                            Doanh Thu</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-white mt-1">
                            {{ number_format($stats['total_revenue']) }} <span
                                class="text-xs font-normal text-gray-400">VND</span></h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-950/50 flex items-center justify-center text-primary-600 dark:text-primary-400">
                        <x-heroicon-o-banknotes class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-300">
                        {{ $stats['total_orders'] }} Đơn hàng
                    </span>
                    <span>Tất cả các dự án</span>
                </div>
            </div>

            <!-- Giá Vốn (COGS) -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Giá
                            Vốn Dự Án (COGS)</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-amber-600 dark:text-amber-400 mt-1">
                            {{ number_format($stats['total_cogs']) }} <span
                                class="text-xs font-normal text-gray-400">VND</span></h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-scale class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    @php
                        $cogsRatio = $stats['total_revenue'] > 0 ? round(($stats['total_cogs'] / $stats['total_revenue']) * 100, 1) : 0;
                    @endphp
                    <span>Chiếm <strong>{{ $cogsRatio }}%</strong> tổng doanh thu</span>
                </div>
            </div>

            <!-- Lợi Nhuận Gộp -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lợi
                            Nhuận Gộp (Gross Profit)</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ number_format($stats['gross_profit']) }} <span
                                class="text-xs font-normal text-gray-400">VND</span></h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-arrow-trending-up class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        ✓ Lãi gộp thực tế
                    </span>
                </div>
            </div>

            <!-- Biên Lợi Nhuận Trung Bình -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Biên
                            Lợi Nhuận Gộp (Margin)</p>
                        <h3 class="text-3xl font-black text-blue-600 dark:text-blue-400 mt-1">
                            {{ $stats['avg_margin'] }}%</h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <x-heroicon-o-chart-pie class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-blue-500 h-1.5 rounded-full"
                            style="width: {{ min(100, $stats['avg_margin']) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TOP HIGH-PROFIT EVENT PROJECTS CARDS --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-sparkles class="w-5 h-5 text-amber-500" />
                        Top Dự Án Doanh Thu & Lợi Nhuận Cao Nhất
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Các sự kiện mang lại dòng tiền và biên lợi nhuận
                        dẫn đầu</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($topProjects as $project)
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between hover:border-primary-500 transition-all">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span
                                        class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ $project['order_no'] }}</span>
                                    <h4 class="text-base font-bold text-gray-900 dark:text-white line-clamp-1 mt-0.5"
                                        title="{{ $project['event'] }}">{{ $project['event'] }}</h4>
                                </div>
                                <span
                                    class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 shrink-0">
                                    {{ $project['margin'] }}% LN
                                </span>
                            </div>

                            <div class="mt-2 text-xs text-gray-500 space-y-0.5">
                                <p>Khách hàng: <strong
                                        class="text-gray-700 dark:text-gray-300">{{ $project['customer'] }}</strong></p>
                                <p>Sales: <span class="text-gray-600 dark:text-gray-400">{{ $project['sales_user'] }}</span>
                                    • Ngày: <span>{{ $project['date'] }}</span></p>
                            </div>

                            <!-- Financial Breakdown Grid -->
                            <div class="grid grid-cols-2 gap-2 mt-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Doanh thu</span>
                                    <p class="text-sm font-extrabold text-gray-900 dark:text-white mt-0.5">
                                        {{ number_format($project['revenue']) }} đ</p>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400">Lợi
                                        nhuận gộp</span>
                                    <p class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                        +{{ number_format($project['profit']) }} đ</p>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar of Profitability -->
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex justify-between items-center text-xs mb-1 text-gray-500">
                                <span>Tỷ trọng lợi nhuận</span>
                                <span class="font-bold text-emerald-600">{{ $project['margin'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-linear-to-r from-emerald-500 to-teal-400 h-2 rounded-full"
                                    style="width: {{ min(100, $project['margin']) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- DETAILED DATA TABLE --}}
        <div class="mt-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-table-cells class="w-5 h-5 text-gray-500" />
                    Bảng Kê Chi Tiết Doanh Thu & Lợi Nhuận Từng Đơn Hàng
                </h3>
            </div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>