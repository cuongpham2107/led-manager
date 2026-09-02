<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $funnel = $this->getSalesFunnel();
        $leaderboard = $this->getSalesLeaderboard();
    @endphp

    <div class="space-y-6">
        {{-- TOP STAT CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Pipeline Value -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tổng Giá Trị Báo Giá</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_value']) }} <span class="text-xs font-normal text-gray-400">VND</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <x-heroicon-o-document-text class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ $stats['total'] }} Báo giá</span>
                    <span>đã khởi tạo trong hệ thống</span>
                </div>
            </div>

            <!-- Won Deals (Doanh thu chốt) -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Doanh Thu Chốt Thành Công</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($stats['won_value']) }} <span class="text-xs font-normal text-gray-400">VND</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-trophy class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        ✓ {{ $stats['won'] }} đơn hàng chốt
                    </span>
                </div>
            </div>

            <!-- Tỷ lệ chốt đơn (Win Rate) -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tỷ Lệ Chuyển Đổi (Win Rate)</p>
                        <h3 class="text-3xl font-black text-primary-600 dark:text-primary-400 mt-1">{{ $stats['win_rate'] }}%</h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-950/50 flex items-center justify-center text-primary-600 dark:text-primary-400">
                        <x-heroicon-o-arrow-trending-up class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-primary-500 h-1.5 rounded-full" style="width: {{ min(100, $stats['win_rate']) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Đang theo đuổi & Thất thoát -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Đang Xử Lý & Thất Bại</p>
                        <h3 class="text-3xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $stats['pending'] }} <span class="text-xs font-normal text-gray-400">đang theo</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-clock class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
                    <span>Đang theo: <strong>{{ number_format($stats['pending_value']) }} đ</strong></span>
                    <span>Hủy/Từ chối: <strong class="text-red-500">{{ $stats['lost'] }}</strong></span>
                </div>
            </div>
        </div>

        {{-- VISUAL SALES FUNNEL & LEADERBOARD SPLIT --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Sales Funnel (7 cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-funnel class="w-5 h-5 text-primary-500" />
                            Phễu Chuyển Đổi Bán Hàng (Sales Funnel)
                        </h3>
                        <p class="text-xs text-gray-500">Tiến trình chuyển dịch trạng thái từ Báo giá sơ bộ đến Đơn hàng chính thức</p>
                    </div>
                </div>

                <div class="space-y-3.5 mt-4">
                    @foreach ($funnel as $stage)
                        <div>
                            <div class="flex justify-between items-center text-xs mb-1.5">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $stage['label'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-gray-900 dark:text-white">{{ $stage['count'] }} báo giá</span>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                        {{ $stage['pct'] }}%
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-3 overflow-hidden">
                                <div class="{{ $stage['color'] }} h-3 rounded-full transition-all duration-500" style="width: {{ max(3, $stage['pct']) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Sales Rep Leaderboard (5 cols) -->
            <div class="lg:col-span-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-fire class="w-5 h-5 text-amber-500" />
                            Bảng Xếp Hạng Doanh Số Sales
                        </h3>
                        <p class="text-xs text-gray-500">Hiệu suất chuyển đổi theo từng nhân sự kinh doanh</p>
                    </div>
                </div>

                <div class="space-y-3 mt-4">
                    @forelse ($leaderboard as $index => $rep)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-black {{ $index === 0 ? 'bg-amber-400 text-black' : ($index === 1 ? 'bg-gray-300 text-black' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300') }}">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $rep['name'] }}</h4>
                                    <span class="text-xs text-gray-500">{{ $rep['won'] }} / {{ $rep['total'] }} đơn chốt ({{ $rep['win_rate'] }}%)</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($rep['won_value']) }} đ</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic text-center py-6">Chưa có dữ liệu nhân viên kinh doanh</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- DETAILED TABLE --}}
        <div class="mt-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-table-cells class="w-5 h-5 text-gray-500" />
                    Bảng Thống Kê Chi Tiết Hiệu Suất Từng Nhân Viên
                </h3>
            </div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
