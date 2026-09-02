<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $lineFailures = $this->getProductLineFailureStats();
    @endphp

    <div class="space-y-6">
        {{-- TOP STAT CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Tổng số ca bảo trì -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tổng Ca Bảo Trì / Sửa</p>
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($stats['total']) }} <span class="text-xs font-normal text-gray-400">phiếu</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <x-heroicon-o-wrench-screwdriver class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                        {{ $stats['pending'] }} Ca đang xử lý
                    </span>
                </div>
            </div>

            <!-- Đã sửa xong (Fixed) -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Đã Phục Hồi Sẵn Sàng</p>
                        <h3 class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($stats['fixed']) }} <span class="text-xs font-normal text-gray-400">cabinets</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <x-heroicon-o-check-badge class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span class="font-bold text-emerald-600">{{ $stats['fix_rate'] }}%</span>
                    <span>tỷ lệ sửa chữa thành công</span>
                </div>
            </div>

            <!-- Tổng chi phí sửa chữa -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tổng Chi Phí Bảo Dưỡng</p>
                        <h3 class="text-2xl lg:text-3xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ number_format($stats['total_cost']) }} <span class="text-xs font-normal text-gray-400">VND</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center text-rose-600 dark:text-rose-400">
                        <x-heroicon-o-currency-dollar class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span>Bao gồm linh kiện & nhân công kỹ thuật</span>
                </div>
            </div>

            <!-- Hỏng nặng thanh lý -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hỏng Nặng (Thanh Lý)</p>
                        <h3 class="text-3xl font-black text-gray-700 dark:text-gray-300 mt-1">{{ number_format($stats['disposed']) }} <span class="text-xs font-normal text-gray-400">cabinets</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-400">
                        <x-heroicon-o-trash class="w-6 h-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                    <span>Không thể phục hồi linh kiện</span>
                </div>
            </div>
        </div>

        {{-- VISUAL FAILURE RATE BY PRODUCT LINE --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-cpu-chip class="w-5 h-5 text-amber-500" />
                        Tần Suất Sự Cố Theo Dòng Sản Phẩm
                    </h3>
                    <p class="text-xs text-gray-500">Đánh giá dòng module/cabinet nào phát sinh chi phí và số lượt bảo dưỡng nhiều nhất</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($lineFailures as $lf)
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-xs flex flex-col justify-between hover:border-amber-400 transition-all">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $lf['code'] }}</span>
                                    <h4 class="text-base font-bold text-gray-900 dark:text-white mt-0.5">{{ $lf['name'] }}</h4>
                                </div>
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                                    {{ $lf['count'] }} ca sửa
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 mt-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Đã sửa xong</span>
                                    <p class="text-base font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $lf['fixed'] }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-rose-500">Chi phí sửa</span>
                                    <p class="text-sm font-black text-rose-600 dark:text-rose-400 mt-0.5">{{ number_format($lf['cost']) }} đ</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center text-xs text-gray-500">
                            <span>Đang sửa chữa: <strong class="text-amber-600">{{ $lf['pending'] }}</strong></span>
                            <span>Môi trường: <strong class="uppercase text-gray-700 dark:text-gray-300">{{ $lf['environment'] }}</strong></span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center text-gray-400 italic">
                        Hiện tại toàn bộ thiết bị đang hoạt động ổn định, không có phiếu sửa chữa nào.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- DETAILED LOG TABLE --}}
        <div class="mt-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-table-cells class="w-5 h-5 text-gray-500" />
                    Bảng Kê Chi Tiết Nhật Ký Bảo Trì & Sự Cố
                </h3>
            </div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
