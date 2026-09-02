<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $productLines = $this->getProductLineStats();
        $warehouses = $this->getWarehouseStats();
    @endphp

    <div class="space-y-6" x-data="{ activeTab: 'warehouses' }">
        {{-- KPI STATS OVERVIEW --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Tổng số lượng thiết bị -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tổng
                            Thiết Bị Toàn Hệ Thống</p>
                        <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">
                            {{ number_format($stats['total']) }} <span
                                class="text-xs font-normal text-gray-400">cabinets</span></h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-950/50 flex items-center justify-center text-primary-600 dark:text-primary-400">
                        <x-heroicon-o-square-3-stack-3d class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">
                        {{ $stats['ready_rate'] }}% Sẵn sàng
                    </span>
                    <span>Tại {{ count($warehouses) }} tổng kho vận hành</span>
                </div>
            </div>

            <!-- Đang chạy sự kiện / Vận chuyển (Khai thác) -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Đang
                            Đi Sự Kiện / Xuất Kho</p>
                        <h3 class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">
                            {{ number_format($stats['in_event']) }} <span
                                class="text-xs font-normal text-gray-400">cabinets</span></h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <x-heroicon-o-tv class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                        ⚡ {{ $stats['utilization_rate'] }}% Tỷ lệ khai thác
                    </span>
                    <span class="text-gray-500">Đang sinh doanh thu</span>
                </div>
            </div>

            <!-- Sẵn sàng trong kho -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sẵn
                            Sàng Cho Thuê</p>
                        <h3 class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ number_format($stats['ready']) }} <span
                                class="text-xs font-normal text-gray-400">cabinets</span></h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-check-badge class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $stats['ready_rate'] }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Đang bảo trì / Sửa chữa -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Đang
                            Bảo Dưỡng / Lỗi</p>
                        <h3 class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">
                            {{ number_format($stats['repairing']) }} <span
                                class="text-xs font-normal text-gray-400">cabinets</span></h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-wrench-screwdriver class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Mất: <strong class="text-red-500">{{ $stats['missing'] }}</strong></span>
                    <span>Đã thanh lý: <strong>{{ $stats['disposed'] }}</strong></span>
                </div>
            </div>
        </div>

        {{-- VIEW MODE NAVIGATION TABS --}}
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
            <div class="flex items-center gap-2">
                <button type="button" @click="activeTab = 'warehouses'"
                    :class="activeTab === 'warehouses' ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-300 border-primary-500 dark:border-primary-500' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-white'"
                    class="px-4 py-2 text-sm font-bold rounded-xl border flex items-center gap-2 transition-all shadow-2xs">
                    <x-heroicon-o-building-office-2 class="w-4 h-4" />
                    Báo Cáo Theo Kho Hàng ({{ count($warehouses) }} Kho)
                </button>
                <button type="button" @click="activeTab = 'products'"
                    :class="activeTab === 'products' ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-300 border-primary-500 dark:border-primary-500' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-white'"
                    class="px-4 py-2 text-sm font-bold rounded-xl border flex items-center gap-2 transition-all shadow-2xs">
                    <x-heroicon-o-square-3-stack-3d class="w-4 h-4" />
                    Báo Cáo Theo Dòng Sản Phẩm ({{ count($productLines) }} Dòng LED)
                </button>
            </div>
        </div>

        {{-- TAB 1: BÁO CÁO THEO KHO HÀNG (WAREHOUSES VIEW) --}}
        <div x-show="activeTab === 'warehouses'" x-transition class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-building-office-2 class="w-5 h-5 text-primary-500" />
                        Tình Trạng Thiết Bị & Khai Thác Tại Từng Tổng Kho
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Theo dõi lượng tồn kho, năng lực cung ứng và
                        tiến độ xuất kho theo từng vùng miền</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-5">
                @foreach ($warehouses as $wh)
                    @php
                        $whRateColor = $wh['rate'] >= 70 ? 'text-red-600 dark:text-red-400' : ($wh['rate'] >= 40 ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400');
                    @endphp
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between hover:border-primary-500 transition-all">
                        <div>
                            <!-- Warehouse Header -->
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="px-2.5 py-0.5 text-xs font-extrabold rounded-md bg-primary-100 text-primary-800 dark:bg-primary-950 dark:text-primary-300 uppercase">
                                            {{ $wh['code'] }}
                                        </span>
                                        <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $wh['name'] }}</h4>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1">
                                        <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                                        <span>{{ $wh['address'] }}</span>
                                    </p>
                                    @if ($wh['phone'] && $wh['phone'] !== '—')
                                        <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                                            <x-heroicon-o-phone class="w-3.5 h-3.5 shrink-0" />
                                            <span>{{ $wh['phone'] }}</span>
                                        </p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-gray-400 block uppercase">Tổng thiết bị</span>
                                    <span
                                        class="text-xl font-black text-gray-900 dark:text-white">{{ number_format($wh['total']) }}</span>
                                </div>
                            </div>

                            <!-- Warehouse Utilization Progress -->
                            <div class="mt-4">
                                <div class="flex justify-between items-baseline mb-1.5">
                                    <span
                                        class="text-xs font-medium text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                                        Tỷ lệ khai thác tại kho
                                    </span>
                                    <span class="text-base font-black {{ $whRateColor }}">{{ $wh['rate'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-blue-600 dark:bg-blue-500 h-2.5 rounded-full transition-all duration-500"
                                        style="width: {{ $wh['rate'] }}%"></div>
                                </div>
                            </div>

                            <!-- Warehouse Metrics Grid -->
                            <div
                                class="grid grid-cols-3 gap-2 mt-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 text-center">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Sẵn sàng</span>
                                    <p class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                        {{ $wh['ready'] }}</p>
                                    <span class="text-[10px] text-gray-400">({{ $wh['ready_rate'] }}%)</span>
                                </div>
                                <div class="border-x border-gray-200 dark:border-gray-700/50">
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Sự kiện / Xuất kho</span>
                                    <p class="text-sm font-extrabold text-blue-600 dark:text-blue-400 mt-0.5">
                                        {{ $wh['in_event'] }}</p>
                                    <span class="text-[10px] text-gray-400">({{ $wh['rate'] }}%)</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Bảo dưỡng</span>
                                    <p class="text-sm font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">
                                        {{ $wh['repairing'] }}</p>
                                    <span class="text-[10px] text-gray-400">({{ $wh['repairing_rate'] }}%)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Product Lines Breakdown in this Warehouse -->
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-500">
                            <span class="text-[11px] font-semibold text-gray-400 block mb-1.5">Chủng loại LED lưu
                                kho:</span>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($wh['product_lines'] as $lineName => $count)
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-medium">
                                        {{ $lineName }}: <strong
                                            class="ml-1 text-primary-600 dark:text-primary-400">{{ $count }} cab</strong>
                                    </span>
                                @empty
                                    <span class="text-gray-400 italic text-[11px]">Kho hiện không có thiết bị</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- TAB 2: BÁO CÁO THEO DÒNG SẢN PHẨM (PRODUCT LINES VIEW) --}}
        <div x-show="activeTab === 'products'" x-transition class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-square-3-stack-3d class="w-5 h-5 text-primary-500" />
                        Tỷ Lệ Khai Thác & Phân Bổ Theo Dòng Sản Phẩm
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Đánh giá sức chứa và tỷ lệ lấp đầy của từng
                        chủng loại LED</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($productLines as $line)
                    @php
                        $rateColor = $line['rate'] >= 70 ? 'text-red-600 dark:text-red-400' : ($line['rate'] >= 40 ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400');
                        $badgeBg = $line['environment'] === 'outdoor' ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300';
                    @endphp
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between hover:border-primary-500 transition-all">
                        <div>
                            <!-- Header card -->
                            <div class="flex items-start justify-between">
                                <div>
                                    <span
                                        class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ $line['code'] }}</span>
                                    <h4 class="text-base font-bold text-gray-900 dark:text-white mt-0.5">{{ $line['name'] }}
                                    </h4>
                                </div>
                                <span
                                    class="px-2.5 py-1 text-xs font-semibold rounded-full uppercase tracking-wider {{ $badgeBg }}">
                                    {{ $line['environment'] }}
                                </span>
                            </div>

                            <!-- Spec tags -->
                            <div class="flex items-center gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>Pitch: <strong>P{{ $line['pixel_pitch'] }}</strong></span>
                                <span>•</span>
                                <span>Cabinet: <strong>{{ $line['cabinet_size'] }}</strong></span>
                                <span>•</span>
                                <span>Tổng: <strong
                                        class="text-gray-900 dark:text-white">{{ $line['total'] }}</strong></span>
                            </div>

                            <!-- Utilization Rate Bar (Accurate Single Metric) -->
                            <div class="mt-4">
                                <div class="flex justify-between items-baseline mb-1.5">
                                    <span
                                        class="text-xs font-medium text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                                        Tỷ lệ khai thác (Đang sự kiện / Xuất kho)
                                    </span>
                                    <span class="text-base font-black {{ $rateColor }}">{{ $line['rate'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-blue-600 dark:bg-blue-500 h-2.5 rounded-full transition-all duration-500"
                                        style="width: {{ $line['rate'] }}%"></div>
                                </div>
                            </div>

                            <!-- Metrics Count Grid -->
                            <div
                                class="grid grid-cols-3 gap-2 mt-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 text-center">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Sẵn sàng</span>
                                    <p class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                        {{ $line['ready'] }}</p>
                                    <span class="text-[10px] text-gray-400">({{ $line['ready_rate'] }}%)</span>
                                </div>
                                <div class="border-x border-gray-200 dark:border-gray-700/50">
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Sự kiện</span>
                                    <p class="text-sm font-extrabold text-blue-600 dark:text-blue-400 mt-0.5">
                                        {{ $line['in_event'] }}</p>
                                    <span class="text-[10px] text-gray-400">({{ $line['rate'] }}%)</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Bảo dưỡng</span>
                                    <p class="text-sm font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">
                                        {{ $line['repairing'] }}</p>
                                    <span class="text-[10px] text-gray-400">({{ $line['repairing_rate'] }}%)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Warehouse Distribution Footer -->
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-500">
                            <span class="text-[11px] font-semibold text-gray-400 block mb-1.5">Phân bổ tại các tổng
                                kho:</span>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($line['warehouses'] as $whName => $count)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                        {{ $whName }}: <strong class="ml-1 text-gray-900 dark:text-white">{{ $count }}</strong>
                                    </span>
                                @empty
                                    <span class="text-gray-400 italic text-[11px]">Chưa có thiết bị</span>
                                @endforelse
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
                    Bảng Chi Tiết Dữ Liệu Khai Thác
                </h3>
            </div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>