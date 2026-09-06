<div
    x-data="{
        state: $wire.$entangle('{{ $getStatePath() }}'),
        isEdit: {{ ($isEdit ?? false) ? 'true' : 'false' }},
        addMoreMode: false,
        search: '',
        apiUrl: '{{ $apiUrl ?? route('filament.checkin-assets') }}',
        allAssets: {{ \Illuminate\Support\Js::from($initialAssets ?? []) }},
        assets: {{ \Illuminate\Support\Js::from($initialAssets ?? []) }},
        loading: false,
        loadingMore: false,
        page: 1,
        hasMore: {{ ($isEdit ?? false) ? 'false' : 'true' }},
        total: {{ ($isEdit ?? false) ? count($initialAssets ?? []) : 0 }},
        searchTimer: null,

        init() {
            if (this.isEdit) {
                // In edit mode: strictly display existing assets of this batch
                if (!Array.isArray(this.state) || this.state.length === 0) {
                    this.state = this.allAssets.map(a => Number(a.id));
                }
                this.total = this.allAssets.length;
                return;
            }

            this.fetchAssets(1, false);
        },

        get selectedList() {
            return Array.isArray(this.state) ? this.state.map(Number) : [];
        },

        isChecked(id) {
            return this.selectedList.includes(Number(id));
        },

        toggle(id) {
            id = Number(id);
            let current = Array.isArray(this.state) ? [...this.state.map(Number)] : [];
            const idx = current.indexOf(id);
            if (idx > -1) {
                current.splice(idx, 1);
            } else {
                current.push(id);
                if (this.addMoreMode) {
                    const item = this.assets.find(a => Number(a.id) === id);
                    if (item && !this.allAssets.some(a => Number(a.id) === id)) {
                        this.allAssets.push(item);
                    }
                }
            }
            this.state = current;
        },

        toggleAllVisible() {
            const visibleIds = this.assets.map(a => Number(a.id));
            let current = Array.isArray(this.state) ? [...this.state.map(Number)] : [];
            const allChecked = visibleIds.length > 0 && visibleIds.every(id => current.includes(id));

            if (allChecked) {
                this.state = current.filter(id => !visibleIds.includes(id));
            } else {
                const combined = new Set([...current, ...visibleIds]);
                this.state = Array.from(combined);
            }
        },

        onSearchInput() {
            if (this.isEdit && !this.addMoreMode) {
                const q = this.search.toLowerCase().trim();
                if (!q) {
                    this.assets = [...this.allAssets];
                } else {
                    this.assets = this.allAssets.filter(a =>
                        (a.serial_no && a.serial_no.toLowerCase().includes(q)) ||
                        (a.name && a.name.toLowerCase().includes(q)) ||
                        (a.size && a.size.toLowerCase().includes(q))
                    );
                }
                return;
            }

            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                this.page = 1;
                this.fetchAssets(1, false);
            }, 250);
        },

        clearSearch() {
            this.search = '';
            if (this.isEdit && !this.addMoreMode) {
                this.assets = [...this.allAssets];
                return;
            }
            this.page = 1;
            this.fetchAssets(1, false);
        },

        startAddMore() {
            this.addMoreMode = true;
            this.search = '';
            this.page = 1;
            this.fetchAssets(1, false);
        },

        backToExisting() {
            this.addMoreMode = false;
            this.search = '';
            this.assets = [...this.allAssets];
        },

        onScroll(e) {
            if (this.isEdit && !this.addMoreMode) return;
            const el = e.target;
            if (!el || this.loading || this.loadingMore || !this.hasMore) return;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 60) {
                this.fetchAssets(this.page + 1, true);
            }
        },

        async fetchAssets(targetPage = 1, append = false) {
            if (append) {
                this.loadingMore = true;
            } else {
                this.loading = true;
            }

            try {
                const url = new URL(this.apiUrl, window.location.origin);
                url.searchParams.set('page', targetPage);
                url.searchParams.set('per_page', 30);
                if (this.search && this.search.trim()) {
                    url.searchParams.set('search', this.search.trim());
                }

                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) throw new Error('Network response not ok');

                const data = await res.json();
                const newItems = data.items || [];

                if (append) {
                    const existingIds = new Set(this.assets.map(a => a.id));
                    for (const item of newItems) {
                        if (!existingIds.has(item.id)) {
                            this.assets.push(item);
                            existingIds.add(item.id);
                        }
                    }
                } else {
                    this.assets = newItems;
                }

                this.page = data.current_page || targetPage;
                this.hasMore = !!data.has_more;
                this.total = data.total || 0;
            } catch (err) {
                console.error('Lỗi khi tải danh sách thiết bị:', err);
            } finally {
                this.loading = false;
                this.loadingMore = false;
            }
        }
    }"
    class="space-y-3 relative z-0"
>
    <div class="flex items-center justify-between gap-2">
        <div>
            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                <span x-show="!isEdit">Mã hàng trong đợt</span>
                <span x-show="isEdit && !addMoreMode">Danh sách thiết bị trong đợt nhập (<span x-text="allAssets.length"></span>)</span>
                <span x-show="isEdit && addMoreMode" class="text-primary-600 font-semibold">Chọn thêm thiết bị từ kho</span>
            </label>
            <p x-show="isEdit && !addMoreMode" class="text-xs text-gray-400 mt-0.5">
                Bỏ tích chọn thiết bị nếu muốn loại bỏ khỏi đợt nhập này.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Đã chọn: <strong class="text-primary-600 font-semibold" x-text="selectedList.length">0</strong>
                <span x-show="!isEdit && total > 0" class="text-gray-400"> / Tổng <span x-text="total"></span></span>
                <span x-show="isEdit && !addMoreMode" class="text-gray-400"> / <span x-text="allAssets.length"></span> trong đợt</span>
            </span>
            <template x-if="isEdit && !addMoreMode">
                <button
                    type="button"
                    @click="startAddMore()"
                    class="px-2.5 py-1 text-xs font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/40 hover:bg-primary-100 rounded-md transition-colors inline-flex items-center gap-1 border border-primary-200 dark:border-primary-800 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Thêm từ kho</span>
                </button>
            </template>
            <template x-if="isEdit && addMoreMode">
                <button
                    type="button"
                    @click="backToExisting()"
                    class="px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 rounded-md transition-colors inline-flex items-center gap-1 cursor-pointer"
                >
                    <span>← Quay lại danh sách đợt (<span x-text="selectedList.length"></span>)</span>
                </button>
            </template>
        </div>
    </div>

    <!-- Search Input with Lazy Search -->
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input
            type="text"
            x-model="search"
            @input="onSearchInput()"
            :placeholder="isEdit && !addMoreMode ? 'Tìm kiếm trong các thiết bị của đợt nhập này...' : 'Tìm nhanh theo số seri, dòng sản phẩm, kích thước...'"
            class="w-full pl-9 pr-8 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-xs"
        />
        <button
            type="button"
            x-show="search.length > 0"
            @click="clearSearch()"
            class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-sm cursor-pointer"
        >
            ✕
        </button>
    </div>

    <!-- Interactive Table with Infinite Scroll / Lazy Loading -->
    <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-xs">
        <div
            class="max-h-72 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800"
            @scroll.passive="onScroll($event)"
        >
            <table class="w-full text-left text-sm border-collapse">
                <thead class="sticky top-0 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-xs text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider z-1 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="w-12 px-4 py-3 text-center">
                            <input
                                type="checkbox"
                                @click="toggleAllVisible()"
                                :checked="assets.length > 0 && assets.every(a => isChecked(a.id))"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-4 h-4 cursor-pointer"
                            />
                        </th>
                        <th class="px-4 py-3">SỐ SERI</th>
                        <th class="px-4 py-3">DÒNG SẢN PHẨM</th>
                        <th class="px-4 py-3">KÍCH THƯỚC</th>
                        <th class="px-4 py-3">TRẠNG THÁI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <!-- Skeleton Loader when loading initial page -->
                    <template x-if="loading && assets.length === 0">
                        <template x-for="i in 5" :key="i">
                            <tr class="animate-pulse">
                                <td class="px-4 py-3.5 text-center">
                                    <div class="w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded mx-auto"></div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-28"></div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-36"></div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
                                </td>
                            </tr>
                        </template>
                    </template>

                    <!-- Render loaded assets -->
                    <template x-for="item in assets" :key="item.id">
                        <tr
                            @click="toggle(item.id)"
                            class="hover:bg-blue-50/40 dark:hover:bg-gray-800/60 cursor-pointer transition-colors"
                            :class="isChecked(item.id) ? 'bg-blue-50/30 dark:bg-blue-950/20' : ''"
                        >
                            <td class="px-4 py-3 text-center" @click.stop>
                                <input
                                    type="checkbox"
                                    :value="item.id"
                                    :checked="isChecked(item.id)"
                                    @change="toggle(item.id)"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-4 h-4 cursor-pointer"
                                />
                            </td>
                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white font-mono text-sm" x-text="item.serial_no"></td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium" x-text="item.name"></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="item.size"></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300': item.status_color === 'success',
                                        'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': item.status_color === 'info',
                                        'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300': item.status_color === 'warning',
                                        'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300': item.status_color === 'danger',
                                        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300': !['success', 'info', 'warning', 'danger'].includes(item.status_color)
                                    }"
                                    x-text="item.status"
                                ></span>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty state -->
                    <tr x-show="!loading && assets.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                            <span x-show="isEdit && allAssets.length === 0">Đợt nhập này hiện chưa có thiết bị nào.</span>
                            <span x-show="!(isEdit && allAssets.length === 0)">Không tìm thấy thiết bị nào khớp với từ khóa tìm kiếm.</span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Bottom loading indicator for infinite scroll / lazy load -->
            <div x-show="loadingMore" class="py-3 px-4 flex items-center justify-center space-x-2 text-xs text-gray-500 bg-gray-50/50 dark:bg-gray-800/50">
                <svg class="animate-spin w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Đang tải thêm thiết bị...</span>
            </div>

            <!-- Manual Load More button if has more and not loading -->
            <div x-show="(!isEdit || addMoreMode) && hasMore && !loading && !loadingMore && assets.length > 0" class="py-2.5 px-4 text-center bg-gray-50/30 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-800">
                <button
                    type="button"
                    @click="fetchAssets(page + 1, true)"
                    class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 font-medium inline-flex items-center space-x-1 cursor-pointer"
                >
                    <span>+ Tải thêm thiết bị (<span x-text="assets.length"></span>/<span x-text="total"></span>)</span>
                </button>
            </div>
        </div>
    </div>
</div>
