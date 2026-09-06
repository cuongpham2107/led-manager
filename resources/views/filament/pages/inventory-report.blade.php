<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $warehouses = $this->getWarehouseBreakdown();
        $productLines = $this->getProductLineBreakdown();
    @endphp

    <div class="space-y-6" x-data="{ activeTab: 'warehouses' }">
        {{-- KPI STATS OVERVIEW --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Tổng số lượng thiết bị -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Tổng Thiết Bị Kho
                        </p>
                        <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">
                            {{ number_format($stats['total']) }}
                            <span class="text-xs font-normal text-gray-400">cabinets</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-950/50 flex items-center justify-center text-primary-600 dark:text-primary-400">
                        <x-heroicon-o-archive-box class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-300">
                        {{ $stats['ready_area'] }} m² sẵn sàng
                    </span>
                    <span>Tại {{ count($warehouses) }} kho lưu trữ</span>
                </div>
            </div>

            <!-- Sẵn sàng xuất kho -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Sẵn Sàng Xuất Kho
                        </p>
                        <h3 class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                            {{ number_format($stats['ready']) }}
                            <span class="text-xs font-normal text-gray-400">cabinets</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-check-badge class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                        {{ $stats['ready_rate'] }}% Tồn kho khả dụng
                    </span>
                    <span class="text-gray-500">Đã kiểm tra kỹ thuật</span>
                </div>
            </div>

            <!-- Đang đi sự kiện / Vận chuyển -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Đang Xuất Ngoài Kho
                        </p>
                        <h3 class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">
                            {{ number_format($stats['in_event']) }}
                            <span class="text-xs font-normal text-gray-400">cabinets</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <x-heroicon-o-truck class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                        {{ $stats['in_event_rate'] }}% Đang phục vụ show
                    </span>
                </div>
            </div>

            <!-- Bảo dưỡng / Lỗi -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Bảo Dưỡng / Sửa Chữa
                        </p>
                        <h3 class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">
                            {{ number_format($stats['repairing']) }}
                            <span class="text-xs font-normal text-gray-400">cabinets</span>
                        </h3>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-wrench-screwdriver class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Thất lạc / Thiếu: <strong class="text-red-500">{{ $stats['missing'] }}</strong></span>
                    <span>Cần bảo trì</span>
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
                    Tồn Kho Theo Chi Nhánh ({{ count($warehouses) }} Kho)
                </button>
                <button type="button" @click="activeTab = 'products'"
                    :class="activeTab === 'products' ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-300 border-primary-500 dark:border-primary-500' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border-transparent hover:text-gray-900 dark:hover:text-white'"
                    class="px-4 py-2 text-sm font-bold rounded-xl border flex items-center gap-2 transition-all shadow-2xs">
                    <x-heroicon-o-square-3-stack-3d class="w-4 h-4" />
                    Tồn Kho Theo Dòng Module LED ({{ count($productLines) }} Dòng)
                </button>
            </div>
        </div>

        {{-- TAB 1: TỒN KHO THEO KHO HÀNG --}}
        <div x-show="activeTab === 'warehouses'" x-transition class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($warehouses as $wh)
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between hover:border-primary-500 transition-all">
                        <div>
                            <div class="flex items-start justify-between">
                                <div>
                                    <span
                                        class="px-2.5 py-0.5 text-xs font-extrabold rounded-md bg-primary-100 text-primary-800 dark:bg-primary-950 dark:text-primary-300 uppercase">
                                        {{ $wh['code'] }}
                                    </span>
                                    <h4 class="text-base font-bold text-gray-900 dark:text-white mt-1">{{ $wh['name'] }}</h4>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-gray-400 block uppercase">Tổng lưu kho</span>
                                    <span class="text-xl font-black text-gray-900 dark:text-white">{{ number_format($wh['total']) }}</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="flex justify-between items-baseline mb-1.5">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                        Tỷ lệ sẵn sàng tại kho
                                    </span>
                                    <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $wh['ready_rate'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                                        style="width: {{ $wh['ready_rate'] }}%"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 mt-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 text-center">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Sẵn sàng</span>
                                    <p class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $wh['ready'] }}</p>
                                </div>
                                <div class="border-x border-gray-200 dark:border-gray-700/50">
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Sự kiện</span>
                                    <p class="text-sm font-extrabold text-blue-600 dark:text-blue-400 mt-0.5">{{ $wh['in_event'] }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Bảo dưỡng</span>
                                    <p class="text-sm font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">{{ $wh['repairing'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- TAB 2: TỒN KHO THEO DÒNG SẢN PHẨM --}}
        <div x-show="activeTab === 'products'" x-transition class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($productLines as $line)
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between hover:border-primary-500 transition-all">
                        <div>
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ $line['code'] }}</span>
                                    <h4 class="text-base font-bold text-gray-900 dark:text-white mt-0.5">{{ $line['name'] }}</h4>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-gray-400 block uppercase">Diện tích khả dụng</span>
                                    <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ $line['ready_area'] }} m²</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="flex justify-between items-baseline mb-1.5">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                        Tỷ lệ khả dụng
                                    </span>
                                    <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $line['ready_rate'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                                        style="width: {{ $line['ready_rate'] }}%"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 mt-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 text-center">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Sẵn sàng</span>
                                    <p class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $line['ready'] }}</p>
                                </div>
                                <div class="border-x border-gray-200 dark:border-gray-700/50">
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Sự kiện</span>
                                    <p class="text-sm font-extrabold text-blue-600 dark:text-blue-400 mt-0.5">{{ $line['in_event'] }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Bảo dưỡng</span>
                                    <p class="text-sm font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">{{ $line['repairing'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- BẢNG CHI TIẾT DANH SÁCH THIẾT BỊ --}}
        <div class="mt-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-table-cells class="w-5 h-5 text-gray-500" />
                    Chi Tiết Tồn Kho Từng Thiết Bị
                </h3>
            </div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
