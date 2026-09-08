@php
    $statePath = $getStatePath();
@endphp

<div
    wire:key="checkout-assets-selector-{{ ($isEdit ?? false) ? 'edit-' . ($batchId ?? 0) : 'create' }}-{{ $warehouseId ?? 'all' }}-{{ $productLineId ?? 'all' }}"
    x-data="{
        state: $wire.$entangle('{{ $statePath }}'),
        isEdit: {{ ($isEdit ?? false) ? 'true' : 'false' }},
        addMoreMode: false,
        allAssets: {{ \Illuminate\Support\Js::from($initialAssets ?? []) }},
        assets: {{ \Illuminate\Support\Js::from($initialAssets ?? []) }},
        warehouseAssets: [],
        selectedAssetsMap: {},
        requiredArea: {{ (float) ($requiredArea ?? 0) }},
        warehouseId: {{ !empty($warehouseId) ? (int) $warehouseId : 'null' }},
        productLineId: {{ !empty($productLineId) ? (int) $productLineId : 'null' }},
        apiUrl: '{{ $apiUrl ?? route('filament.checkout-assets') }}',
        search: '',
        searchTimer: null,
        page: 1,
        hasMore: true,
        loading: false,
        loadingMore: false,
        totalWarehouse: 0,

        init() {
            if (!Array.isArray(this.state)) {
                this.state = [];
            }

            // Seed map with all initial assets belonging to this batch
            for (const a of this.allAssets) {
                this.selectedAssetsMap[Number(a.id)] = a;
            }

            if (this.isEdit) {
                // Sửa đợt xuất: Chỉ hiển thị danh sách thiết bị trong đợt!
                if (this.state.length === 0 && this.allAssets.length > 0) {
                    this.state = this.allAssets.map(a => Number(a.id));
                }
                this.assets = [...this.allAssets];
            } else {
                // Thêm mới: Tải danh sách từ kho (Lazy loading)
                this.fetchWarehouseAssets(1, false);
            }
        },

        get selectedList() {
            return Array.isArray(this.state) ? this.state.map(Number) : [];
        },

        get selectedArea() {
            const selected = this.selectedList;
            let totalArea = 0;
            for (const id of selected) {
                const asset = this.selectedAssetsMap[id] || this.allAssets.find(a => Number(a.id) === id) || this.warehouseAssets.find(a => Number(a.id) === id);
                if (asset) {
                    totalArea += (parseFloat(asset.area_m2) || 0.25);
                } else {
                    totalArea += 0.25;
                }
            }
            return totalArea;
        },

        isChecked(id) {
            return this.selectedList.includes(Number(id));
        },

        // Chuyển sang chế độ thêm thiết bị từ kho
        startAddMore() {
            this.addMoreMode = true;
            this.search = '';
            if (this.warehouseAssets.length === 0) {
                this.page = 1;
                this.fetchWarehouseAssets(1, false);
            } else {
                this.assets = [...this.warehouseAssets];
            }
        },

        // Quay lại danh sách thiết bị trong đợt
        backToExisting() {
            this.addMoreMode = false;
            this.search = '';

            // Cập nhật lại allAssets: giữ các asset đã có trong đợt đang được check, và thêm các asset mới từ selectedAssetsMap
            const selectedIds = new Set(this.selectedList);
            const updated = [];

            // Giữ lại các thiết bị cũ vẫn được chọn
            for (const item of this.allAssets) {
                if (selectedIds.has(Number(item.id))) {
                    updated.push(item);
                }
            }

            // Thêm các thiết bị mới được chọn từ kho
            const existingIds = new Set(updated.map(a => Number(a.id)));
            for (const id of selectedIds) {
                if (!existingIds.has(id) && this.selectedAssetsMap[id]) {
                    const newItem = { ...this.selectedAssetsMap[id], is_dispatched: true, dispatched_at: 'Vừa thêm' };
                    updated.push(newItem);
                }
            }

            this.allAssets = updated;
            this.assets = [...updated];
        },

        // Xóa một thiết bị khỏi đợt xuất (trong chế độ xem đợt)
        removeItemFromBatch(item) {
            const id = Number(item.id);
            this.allAssets = this.allAssets.filter(a => Number(a.id) !== id);
            this.assets = this.assets.filter(a => Number(a.id) !== id);
            this.state = this.selectedList.filter(selectedId => selectedId !== id);
        },

        // Toggle chọn/bỏ chọn trong bảng chọn từ kho
        toggle(item) {
            const id = Number(item.id);
            let current = [...this.selectedList];
            const idx = current.indexOf(id);

            if (idx > -1) {
                current.splice(idx, 1);
            } else {
                current.push(id);
                this.selectedAssetsMap[id] = item;
            }
            this.state = current;
        },

        toggleAllVisible() {
            const visibleIds = this.assets.map(a => Number(a.id));
            let current = [...this.selectedList];
            const allChecked = visibleIds.length > 0 && visibleIds.every(id => current.includes(id));

            if (allChecked) {
                this.state = current.filter(id => !visibleIds.includes(id));
            } else {
                for (const a of this.assets) {
                    this.selectedAssetsMap[Number(a.id)] = a;
                }
                const combined = new Set([...current, ...visibleIds]);
                this.state = Array.from(combined);
            }
        },

        onScroll(e) {
            const el = e.target;
            if (!el || this.loading || this.loadingMore || !this.hasMore) return;
            // Chỉ cuộn tải thêm khi ở chế độ chọn từ kho (!isEdit || addMoreMode)
            if ((!this.isEdit || this.addMoreMode) && el.scrollTop + el.clientHeight >= el.scrollHeight - 60) {
                this.fetchWarehouseAssets(this.page + 1, true);
            }
        },

        onSearchInput() {
            if (this.isEdit && !this.addMoreMode) {
                // Tìm kiếm local trong allAssets
                const q = this.search.trim().toLowerCase();
                if (!q) {
                    this.assets = [...this.allAssets];
                } else {
                    this.assets = this.allAssets.filter(a =>
                        (a.serial_no && a.serial_no.toLowerCase().includes(q)) ||
                        (a.name && a.name.toLowerCase().includes(q)) ||
                        (a.type && a.type.toLowerCase().includes(q)) ||
                        (a.size && a.size.toLowerCase().includes(q))
                    );
                }
            } else {
                // Tìm kiếm qua API cho kho
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => {
                    this.page = 1;
                    this.fetchWarehouseAssets(1, false);
                }, 300);
            }
        },

        clearSearch() {
            this.search = '';
            if (this.isEdit && !this.addMoreMode) {
                this.assets = [...this.allAssets];
            } else {
                this.page = 1;
                this.fetchWarehouseAssets(1, false);
            }
        },

        async fetchWarehouseAssets(targetPage = 1, append = false) {
            if (append) {
                this.loadingMore = true;
            } else {
                this.loading = true;
            }

            try {
                const url = new URL(this.apiUrl, window.location.origin);
                url.searchParams.set('page', targetPage);
                url.searchParams.set('per_page', 50);

                if (this.warehouseId) {
                    url.searchParams.set('warehouse_id', this.warehouseId);
                }
                if (this.productLineId) {
                    url.searchParams.set('product_line_id', this.productLineId);
                }
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

                for (const item of newItems) {
                    if (this.isChecked(item.id)) {
                        this.selectedAssetsMap[Number(item.id)] = item;
                    }
                }

                if (append) {
                    const existingIds = new Set(this.warehouseAssets.map(a => Number(a.id)));
                    for (const item of newItems) {
                        if (!existingIds.has(Number(item.id))) {
                            this.warehouseAssets.push(item);
                            existingIds.add(Number(item.id));
                        }
                    }
                } else {
                    this.warehouseAssets = [...newItems];
                }

                this.assets = [...this.warehouseAssets];
                this.page = data.current_page || targetPage;
                this.hasMore = !!data.has_more;
                this.totalWarehouse = data.total || 0;

                // Auto suggest for create mode if requiredArea is set
                if (!this.isEdit && this.requiredArea > 0 && this.state.length === 0 && targetPage === 1) {
                    this.autoSuggestByArea(this.requiredArea);
                }
            } catch (err) {
                console.error('Lỗi khi tải danh sách thiết bị kho:', err);
            } finally {
                this.loading = false;
                this.loadingMore = false;
            }
        },

        async autoSuggestByArea(area) {
            if (area <= 0 || this.warehouseAssets.length === 0) return;

            let accumulated = 0;
            const suggestedIds = [];

            for (const asset of this.warehouseAssets) {
                const id = Number(asset.id);
                suggestedIds.push(id);
                this.selectedAssetsMap[id] = asset;
                accumulated += (parseFloat(asset.area_m2) || 0.25);
                if (accumulated >= area) {
                    break;
                }
            }

            this.state = suggestedIds;

            if (accumulated < area && this.hasMore && !this.loading && !this.loadingMore) {
                await this.fetchWarehouseAssets(this.page + 1, true);
                this.autoSuggestByArea(area);
            }
        }
    }"
    class="space-y-3 relative z-0 font-sans"
>
    <!-- Header with labels, counters, and action buttons -->
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                <span x-show="!isEdit">Mã seri gợi ý từ kho</span>
                <span x-show="isEdit && !addMoreMode">Danh sách thiết bị trong đợt xuất (<span x-text="allAssets.length"></span>)</span>
                <span x-show="isEdit && addMoreMode" class="text-primary-600 font-semibold">Chọn thêm thiết bị từ kho</span>
            </label>
            <p x-show="isEdit && !addMoreMode" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Đã gán xuất: <strong class="text-primary-600 font-semibold" x-text="allAssets.length"></strong> thiết bị
                <template x-if="selectedArea > 0">
                    <span> · Tổng diện tích: <strong class="text-primary-600 font-semibold" x-text="selectedArea.toFixed(2)"></strong> m²</span>
                </template>
            </p>
            <p x-show="!isEdit" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Đã chọn: <strong class="text-primary-600 font-semibold" x-text="selectedList.length"></strong>
                <span x-show="totalWarehouse > 0"> / Tổng <span x-text="totalWarehouse"></span> thiết bị sẵn sàng</span>
                <template x-if="selectedArea > 0">
                    <span> · Tổng: <strong class="text-primary-600 font-semibold" x-text="selectedArea.toFixed(2)"></strong> m²</span>
                </template>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Mode switch for Edit Mode: "+ Thêm từ kho" button -->
            <template x-if="isEdit && !addMoreMode">
                <button
                    type="button"
                    @click="startAddMore()"
                    class="px-3 py-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/40 hover:bg-primary-100 dark:hover:bg-primary-900/60 rounded-lg transition-colors inline-flex items-center gap-1.5 border border-primary-200 dark:border-primary-800 cursor-pointer shadow-2xs"
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
                    class="px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors inline-flex items-center gap-1.5 cursor-pointer border border-gray-300 dark:border-gray-600 shadow-2xs"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Quay lại danh sách đợt (<span x-text="selectedList.length"></span>)</span>
                </button>
            </template>

            <template x-if="!isEdit && requiredArea > 0">
                <button
                    type="button"
                    @click="autoSuggestByArea(requiredArea)"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-950/40 dark:text-primary-300 transition-colors border border-primary-200 dark:border-primary-800 inline-flex items-center gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Gợi ý đủ <span x-text="requiredArea"></span> m²
                </button>
            </template>
        </div>
    </div>

    <!-- Search Input -->
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
            :placeholder="isEdit && !addMoreMode ? 'Tìm theo dòng sản phẩm, số seri...' : 'Tìm nhanh theo số seri, dòng sản phẩm, kích thước...'"
            class="w-full pl-9 pr-8 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all shadow-2xs"
        />
        <button
            type="button"
            x-show="search.length > 0"
            @click="clearSearch()"
            class="absolute right-2.5 top-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xs cursor-pointer"
        >
            ✕
        </button>
    </div>

    <!-- Table Container -->
    <div class="border border-gray-200 dark:border-gray-700/80 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-2xs">
        <div
            @scroll.passive="onScroll($event)"
            class="max-h-72 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800"
        >
            <!-- VIEW 1: Existing Batch Items Table (Displayed when editing an existing batch) -->
            <template x-if="isEdit && !addMoreMode">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="sticky top-0 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-xs text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider z-1 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-5 py-3 font-semibold">DÒNG SẢN PHẨM</th>
                            <th class="px-5 py-3 font-semibold">KÍCH THƯỚC / DIỆN TÍCH</th>
                            <th class="px-5 py-3 font-semibold">TRẠNG THÁI XUẤT</th>
                            <th class="px-5 py-3 font-semibold text-right">HÀNH ĐỘNG</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="item in assets" :key="item.id">
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-800/60 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm" x-text="item.name"></div>
                                    <div class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5" x-text="item.serial_no"></div>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-600 dark:text-gray-400">
                                    <span x-text="item.size || '0.5×0.5 m'"></span>
                                    <span class="text-gray-400"> · </span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300" x-text="(item.area_m2 || 0.25) + ' m²'"></span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <template x-if="item.is_dispatched">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Đã xuất kho
                                        </span>
                                    </template>
                                    <template x-if="!item.is_dispatched">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Chờ xuất
                                        </span>
                                    </template>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <button
                                        type="button"
                                        @click="removeItemFromBatch(item)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700 bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-100 dark:hover:bg-rose-900/40 rounded-lg transition-colors cursor-pointer border border-rose-200 dark:border-rose-800/60"
                                        title="Gỡ thiết bị này khỏi đợt xuất"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <span>Xóa</span>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="assets.length === 0">
                            <td colspan="4" class="px-5 py-8 text-center text-gray-400 text-sm">
                                <span x-show="allAssets.length === 0">Đợt xuất này hiện chưa có thiết bị nào. Nhấn "+ Thêm từ kho" để chọn thiết bị.</span>
                                <span x-show="allAssets.length > 0">Không tìm thấy thiết bị nào khớp với từ khóa tìm kiếm.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <!-- VIEW 2: Selection Table with Checkboxes (Create mode / Add More mode) -->
            <template x-if="!isEdit || addMoreMode">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="sticky top-0 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-xs text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider z-1 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="w-10 px-3 py-2.5 text-center">
                                <input
                                    type="checkbox"
                                    @click="toggleAllVisible()"
                                    :checked="assets.length > 0 && assets.every(a => isChecked(a.id))"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 w-4 h-4 cursor-pointer"
                                />
                            </th>
                            <th class="px-4 py-2.5 font-semibold text-gray-500 dark:text-gray-400">SỐ SERI</th>
                            <th class="px-4 py-2.5 font-semibold text-gray-500 dark:text-gray-400">TÊN THIẾT BỊ</th>
                            <th class="px-4 py-2.5 font-semibold text-gray-500 dark:text-gray-400">KÍCH THƯỚC</th>
                            <th class="px-4 py-2.5 font-semibold text-gray-500 dark:text-gray-400">LOẠI THIẾT BỊ</th>
                            <th class="px-4 py-2.5 font-semibold text-gray-500 dark:text-gray-400">TRẠNG THÁI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                        <!-- Render Rows -->
                        <template x-for="item in assets" :key="item.id">
                            <tr
                                @click="toggle(item)"
                                class="hover:bg-blue-50/40 dark:hover:bg-gray-800/60 cursor-pointer transition-colors"
                                :class="isChecked(item.id) ? 'bg-blue-50/20 dark:bg-blue-950/20' : ''"
                            >
                                <td class="px-3 py-2.5 text-center" @click.stop>
                                    <input
                                        type="checkbox"
                                        :value="item.id"
                                        :checked="isChecked(item.id)"
                                        @change="toggle(item)"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 w-4 h-4 cursor-pointer"
                                    />
                                </td>
                                <td class="px-4 py-2.5 font-bold text-gray-900 dark:text-white font-mono text-sm tracking-tight" x-text="item.serial_no"></td>
                                <td class="px-4 py-2.5 text-gray-800 dark:text-gray-200 font-medium" x-text="item.name"></td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 text-xs" x-text="item.size"></td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 text-xs" x-text="item.type"></td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800"
                                        x-text="item.status_label || 'Sẵn sàng trong kho'"
                                    ></span>
                                </td>
                            </tr>
                        </template>

                        <!-- Initial Loading state -->
                        <tr x-show="loading && assets.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-xs">
                                <div class="inline-flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4 text-primary-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Đang tải danh sách thiết bị sẵn sàng...</span>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr x-show="!loading && assets.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">
                                Không có thiết bị phù hợp với điều kiện lọc (Kho hàng / Loại thiết bị / Tìm kiếm).
                            </td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <!-- Infinite Scroll Loading More indicator -->
            <div x-show="loadingMore" class="py-2.5 text-center bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 text-xs text-primary-600 dark:text-primary-400 font-medium flex items-center justify-center gap-2">
                <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Đang tải thêm thiết bị...</span>
            </div>

            <!-- All loaded indicator -->
            <div x-show="!loading && !loadingMore && !hasMore && assets.length > 50 && (!isEdit || addMoreMode)" class="py-2 text-center text-xs text-gray-400">
                Đã hiển thị toàn bộ <span x-text="assets.length"></span> thiết bị
            </div>
        </div>
    </div>
</div>
