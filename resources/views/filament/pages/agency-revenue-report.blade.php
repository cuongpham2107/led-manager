<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $agencies = $this->getAgenciesOverview();
    @endphp

    <div class="space-y-6">
        {{-- KPI STATS CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Tổng Doanh Thu Đơn Hàng -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Doanh Thu Đơn Hàng</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-white mt-1">
                            {{ number_format($stats['total_revenue']) }} <span class="text-xs font-normal text-gray-400">VND</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-950/50 flex items-center justify-center text-primary-600 dark:text-primary-400">
                        <x-heroicon-o-calendar-days class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-300">
                        {{ $stats['total_orders'] }} Đơn hàng
                    </span>
                    <span>Từ các đại lý tỉnh</span>
                </div>
            </div>

            <!-- Tổng Thực Thu -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Thực Thu (Dòng Tiền)</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ number_format($stats['total_collected']) }} <span class="text-xs font-normal text-gray-400">VND</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-banknotes class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    @php
                        $collectRatio = $stats['total_revenue'] > 0 ? round(($stats['total_collected'] / $stats['total_revenue']) * 100, 1) : 0;
                    @endphp
                    <span>Đã thu <strong>{{ $collectRatio }}%</strong> giá trị đơn hàng</span>
                </div>
            </div>

            <!-- Tiền Hoa Hồng Đại Lý -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hoa Hồng Đại Lý (% Thực Thu)</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-amber-600 dark:text-amber-400 mt-1">
                            {{ number_format($stats['total_commission']) }} <span class="text-xs font-normal text-gray-400">VND</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-sparkles class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                        ✓ Ăn theo % thực thu
                    </span>
                    <span>Chi trả hoa hồng</span>
                </div>
            </div>

            <!-- Giám sát Hạn Mức Bàn Giao (1.000m²) -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tồn Kho / Hạn Mức Bàn Giao</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-blue-600 dark:text-blue-400 mt-1">
                            {{ number_format($stats['total_inventory_area'], 1) }} <span class="text-xs font-normal text-gray-400">/ {{ number_format($stats['total_allocated_area'], 1) }} m²</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <x-heroicon-o-cube class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    @php
                        $utilRatio = $stats['total_allocated_area'] > 0 ? round(($stats['total_inventory_area'] / $stats['total_allocated_area']) * 100, 1) : 0;
                    @endphp
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full {{ $utilRatio > 100 ? 'bg-rose-500' : 'bg-blue-500' }}"
                            style="width: {{ min(100, $utilRatio) }}%"></div>
                    </div>
                    <span class="font-bold shrink-0">{{ $utilRatio }}%</span>
                </div>
            </div>
        </div>

        {{-- DANH SÁCH CHI TIẾT TỪNG ĐẠI LÝ --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-building-storefront class="w-5 h-5 text-primary-500" />
                        Danh Sách Đại Lý Tỉnh & Tỷ Lệ Hoa Hồng
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Theo dõi định mức diện tích bàn giao 1.000m² và hoa hồng ăn chia theo doanh thu thực thu
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($agencies as $agency)
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between hover:border-primary-500 transition-all">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ $agency['code'] }} • {{ $agency['province'] }}</span>
                                    <h4 class="text-base font-bold text-gray-900 dark:text-white mt-0.5">
                                        {{ $agency['name'] }}
                                    </h4>
                                </div>
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 shrink-0">
                                    {{ $agency['commission_rate'] }}% Hoa hồng
                                </span>
                            </div>

                            <!-- Financial Breakdown Grid -->
                            <div class="grid grid-cols-2 gap-2 mt-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Doanh số đơn</span>
                                    <p class="text-sm font-extrabold text-gray-900 dark:text-white mt-0.5">
                                        {{ number_format($agency['revenue']) }} đ
                                    </p>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400">Thực thu</span>
                                    <p class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                        {{ number_format($agency['collected']) }} đ
                                    </p>
                                </div>
                                <div class="col-span-2 pt-2 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Hoa hồng đại lý hưởng:</span>
                                    <span class="text-sm font-black text-amber-600 dark:text-amber-400">{{ number_format($agency['commission']) }} đ</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar for Allocated Area Capacity (1.000m2) -->
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <div class="flex justify-between items-center text-xs mb-1 text-gray-500">
                                <span>Tồn kho: <strong>{{ number_format($agency['inventory_area'], 1) }}</strong> / {{ number_format($agency['allocated_area'], 1) }} m²</span>
                                <span class="font-bold {{ $agency['utilization_percent'] > 100 ? 'text-rose-600' : 'text-blue-600' }}">{{ $agency['utilization_percent'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full {{ $agency['utilization_percent'] > 100 ? 'bg-rose-500' : 'bg-linear-to-r from-blue-500 to-cyan-400' }}"
                                    style="width: {{ min(100, $agency['utilization_percent']) }}%"></div>
                            </div>
                            @if ($agency['utilization_percent'] > 100)
                                <p class="text-[11px] text-rose-500 mt-1 font-semibold">⚠️ Đã vượt định mức bàn giao {{ number_format($agency['allocated_area']) }} m²</p>
                            @endif
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
                    Bảng Kê Chi Tiết Đơn Hàng & Đối Soát Hoa Hồng Đại Lý
                </h3>
            </div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
