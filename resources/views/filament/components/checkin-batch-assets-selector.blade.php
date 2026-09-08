<div
    wire:key="checkin-assets-selector-{{ ($isEdit ?? false) ? 'edit-' . ($batchId ?? 0) : 'create' }}-{{ $warehouseId ?? 'all' }}"
    x-data="{
        state: $wire.$entangle('{{ $getStatePath() }}'),
        isEdit: {{ ($isEdit ?? false) ? 'true' : 'false' }},
        addMoreMode: false,
        search: '',
        apiUrl: '{{ $apiUrl ?? route('filament.checkin-assets') }}',
        allAssets: {{ \Illuminate\Support\Js::from($initialAssets ?? []) }},
        assets: {{ \Illuminate\Support\Js::from($initialAssets ?? []) }},
        productLines: {{ \Illuminate\Support\Js::from($productLines ?? []) }},
        statuses: {{ \Illuminate\Support\Js::from($statuses ?? []) }},
        warehouseId: {{ !empty($warehouseId) ? (int) $warehouseId : 'null' }},
        selectedProductLine: '',
        selectedStatus: '',
        selectedCondition: 'all',
        loading: false,
        loadingMore: false,
        page: 1,
        hasMore: {{ ($isEdit ?? false) ? 'false' : 'true' }},
        total: {{ ($isEdit ?? false) ? count($initialAssets ?? []) : 0 }},
        searchTimer: null,

        batchId: {{ !empty($batchId) ? (int) $batchId : 'null' }},
        batchCode: '{{ $batchCode ?? '' }}',
        batchStatus: '{{ $batchStatus ?? 'pending' }}',
        receiveUrl: '{{ $receiveUrl ?? route('filament.checkin-receive-item') }}',
        completeUrl: '{{ $completeUrl ?? route('filament.checkin-complete-batch') }}',
        csrfToken: '{{ csrf_token() }}',

        showReceiveModal: false,
        activeAsset: null,
        receiveCondition: null,
        submittingReceive: false,

        toast: {
            show: false,
            message: '',
            type: 'success',
            timer: null,
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            clearTimeout(this.toast.timer);
            this.toast.timer = setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        },

        init() {
            if (this.isEdit) {
                if (!Array.isArray(this.state) || this.state.length === 0) {
                    this.state = this.allAssets.map(a => Number(a.id));
                }
                this.total = this.allAssets.length;
                return;
            }

            this.fetchAssets(1, false);
        },

        get stats() {
            const total = this.allAssets.length;
            const received = this.allAssets.filter(a => a.is_received).length;
            const normal = this.allAssets.filter(a => a.is_received && (a.condition === 'normal' || a.condition_raw === 'ok')).length;
            const damaged = this.allAssets.filter(a => a.is_received && (a.condition === 'damaged' || a.condition_raw === 'fault')).length;
            const unreceived = total - received;

            return { total, received, normal, damaged, unreceived };
        },

        get hasActiveFilters() {
            return Boolean(
                (this.search && this.search.trim()) ||
                this.selectedProductLine ||
                this.selectedStatus ||
                (this.selectedCondition && this.selectedCondition !== 'all')
            );
        },

        applyFilters() {
            if (this.isEdit && !this.addMoreMode) {
                const q = this.search.toLowerCase().trim();
                this.assets = this.allAssets.filter(a => {
                    const matchesSearch = !q ||
                        (a.serial_no && a.serial_no.toLowerCase().includes(q)) ||
                        (a.name && a.name.toLowerCase().includes(q)) ||
                        (a.size && a.size.toLowerCase().includes(q));

                    const matchesProductLine = !this.selectedProductLine ||
                        Number(a.product_line_id) === Number(this.selectedProductLine);

                    const matchesCondition = !this.selectedCondition || this.selectedCondition === 'all' ||
                        (this.selectedCondition === 'unreceived' && !a.is_received) ||
                        (this.selectedCondition === 'normal' && a.is_received && (a.condition === 'normal' || a.condition_raw === 'ok')) ||
                        (this.selectedCondition === 'damaged' && a.is_received && (a.condition === 'damaged' || a.condition_raw === 'fault'));

                    return matchesSearch && matchesProductLine && matchesCondition;
                });
                return;
            }

            this.page = 1;
            this.fetchAssets(1, false);
        },

        resetFilters() {
            this.search = '';
            this.selectedProductLine = '';
            this.selectedStatus = '';
            this.selectedCondition = 'all';
            this.applyFilters();
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
                this.applyFilters();
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
            this.applyFilters();
        },

        startAddMore() {
            this.addMoreMode = true;
            this.search = '';
            this.selectedProductLine = '';
            this.selectedStatus = '';
            this.page = 1;
            this.fetchAssets(1, false);
        },

        backToExisting() {
            this.addMoreMode = false;
            this.search = '';
            this.selectedProductLine = '';
            this.selectedStatus = '';
            this.selectedCondition = 'all';
            this.applyFilters();
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
                if (this.selectedProductLine) {
                    url.searchParams.set('product_line_id', this.selectedProductLine);
                }
                if (this.selectedStatus) {
                    url.searchParams.set('status', this.selectedStatus);
                }
                if (this.warehouseId) {
                    url.searchParams.set('warehouse_id', this.warehouseId);
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
        },

        openReceiveModal(item) {
            this.activeAsset = item;
            this.receiveCondition = item.condition || 'normal';
            this.showReceiveModal = true;
        },

        closeReceiveModal() {
            this.showReceiveModal = false;
            this.activeAsset = null;
            this.receiveCondition = null;
            this.submittingReceive = false;
        },

        async submitReceive() {
            if (!this.activeAsset || !this.receiveCondition || !this.batchId) return;
            this.submittingReceive = true;

            try {
                const response = await fetch(this.receiveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        batch_id: this.batchId,
                        asset_id: this.activeAsset.id,
                        condition: this.receiveCondition
                    })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    const targetId = Number(this.activeAsset.id);
                    const updateList = (list) => {
                        const target = list.find(a => Number(a.id) === targetId);
                        if (target) {
                            target.is_received = true;
                            target.condition = data.item.condition;
                            target.condition_raw = data.item.condition_raw;
                            target.received_at = data.item.received_at;
                        }
                    };
                    updateList(this.allAssets);
                    updateList(this.assets);

                    this.showToast(data.message || 'Đã nhận hàng thành công!');
                    this.closeReceiveModal();
                } else {
                    alert(data.message || 'Có lỗi xảy ra khi nhận hàng.');
                }
            } catch (err) {
                console.error('Lỗi khi nhận hàng:', err);
                alert('Không thể kết nối đến máy chủ.');
            } finally {
                this.submittingReceive = false;
            }
        }
    }"
    class="space-y-3 relative z-0"
>
    <!-- Top Bar: Labels, Counters & Actions -->
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                <span x-show="!isEdit">Mã hàng trong đợt</span>
                <span x-show="isEdit && !addMoreMode">Danh sách thiết bị trong đợt nhập (<span x-text="allAssets.length"></span>)</span>
                <span x-show="isEdit && addMoreMode" class="text-primary-600 font-semibold">Chọn thêm thiết bị từ kho</span>
            </label>
            <p x-show="isEdit && !addMoreMode" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Tiến độ nhận: <strong class="text-primary-600 font-semibold" x-text="allAssets.filter(a => a.is_received).length"></strong> / <span x-text="allAssets.length"></span> thiết bị
            </p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Mode switch for Edit Mode -->
            <template x-if="isEdit && !addMoreMode">
                <button
                    type="button"
                    @click="startAddMore()"
                    class="px-3 py-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/40 hover:bg-primary-100 rounded-lg transition-colors inline-flex items-center gap-1 border border-primary-200 dark:border-primary-800 cursor-pointer"
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
                    class="px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 rounded-lg transition-colors inline-flex items-center gap-1 cursor-pointer"
                >
                    <span>← Quay lại danh sách đợt (<span x-text="selectedList.length"></span>)</span>
                </button>
            </template>

            <template x-if="!isEdit">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Đã chọn: <strong class="text-primary-600 font-semibold" x-text="selectedList.length">0</strong>
                    <span x-show="total > 0" class="text-gray-400"> / Tổng <span x-text="total"></span></span>
                </span>
            </template>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="space-y-2.5">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            <!-- Search Input -->
            <div class="relative flex-1">
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
                    class="w-full pl-9 pr-8 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all shadow-2xs"
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

            <!-- Filter: Dòng sản phẩm -->
            <div class="w-full sm:w-52">
                <select
                    x-model="selectedProductLine"
                    @change="applyFilters()"
                    class="w-full py-2 px-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent shadow-2xs cursor-pointer"
                >
                    <option value="">Tất cả dòng sản phẩm</option>
                    <template x-for="pl in productLines" :key="pl.id">
                        <option :value="pl.id" x-text="pl.name"></option>
                    </template>
                </select>
            </div>

            <!-- Filter: Trạng thái (chỉ hiện khi chọn từ kho: tạo mới hoặc bấm "Thêm từ kho") -->
            <template x-if="!isEdit || addMoreMode">
                <div class="w-full sm:w-48">
                    <select
                        x-model="selectedStatus"
                        @change="applyFilters()"
                        class="w-full py-2 px-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent shadow-2xs cursor-pointer"
                    >
                        <option value="">Tất cả trạng thái</option>
                        <template x-for="st in statuses" :key="st.value">
                            <option :value="st.value" x-text="st.label"></option>
                        </template>
                    </select>
                </div>
            </template>

            <!-- Nút Reset Filter -->
            <button
                x-show="hasActiveFilters"
                type="button"
                @click="resetFilters()"
                class="px-2.5 py-2 text-xs font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 rounded-lg transition-colors inline-flex items-center gap-1 border border-rose-200 dark:border-rose-800 cursor-pointer shrink-0"
                title="Xóa bộ lọc"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>Xóa lọc</span>
            </button>
        </div>

        <!-- Filter: Tiến độ / Kết quả nhận hàng (chế độ xem đợt nhập) -->
        <template x-if="isEdit && !addMoreMode">
            <div class="flex items-center gap-1.5 flex-wrap pt-1 text-xs">
                <span class="text-gray-500 dark:text-gray-400 font-medium mr-1">Lọc theo kết quả:</span>
                
                <button
                    type="button"
                    @click="selectedCondition = 'all'; applyFilters()"
                    class="px-2.5 py-1 rounded-md font-medium transition-all cursor-pointer inline-flex items-center gap-1"
                    :class="selectedCondition === 'all'
                        ? 'bg-primary-600 text-white shadow-xs font-semibold'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'"
                >
                    <span>Tất cả</span>
                    <span class="opacity-80 text-[11px]" x-text="`(${allAssets.length})`"></span>
                </button>

                <button
                    type="button"
                    @click="selectedCondition = 'unreceived'; applyFilters()"
                    class="px-2.5 py-1 rounded-md font-medium transition-all cursor-pointer inline-flex items-center gap-1"
                    :class="selectedCondition === 'unreceived'
                        ? 'bg-amber-600 text-white shadow-xs font-semibold'
                        : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 hover:bg-amber-100 border border-amber-200/60 dark:border-amber-800/60'"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500" :class="selectedCondition === 'unreceived' ? 'bg-white' : ''"></span>
                    <span>Chưa nhận</span>
                    <span class="text-[11px]" x-text="`(${stats.unreceived})`"></span>
                </button>

                <button
                    type="button"
                    @click="selectedCondition = 'normal'; applyFilters()"
                    class="px-2.5 py-1 rounded-md font-medium transition-all cursor-pointer inline-flex items-center gap-1"
                    :class="selectedCondition === 'normal'
                        ? 'bg-emerald-600 text-white shadow-xs font-semibold'
                        : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 hover:bg-emerald-100 border border-emerald-200/60 dark:border-emerald-800/60'"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" :class="selectedCondition === 'normal' ? 'bg-white' : ''"></span>
                    <span>Bình thường</span>
                    <span class="text-[11px]" x-text="`(${stats.normal})`"></span>
                </button>

                <button
                    type="button"
                    @click="selectedCondition = 'damaged'; applyFilters()"
                    class="px-2.5 py-1 rounded-md font-medium transition-all cursor-pointer inline-flex items-center gap-1"
                    :class="selectedCondition === 'damaged'
                        ? 'bg-rose-600 text-white shadow-xs font-semibold'
                        : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 hover:bg-rose-100 border border-rose-200/60 dark:border-rose-800/60'"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500" :class="selectedCondition === 'damaged' ? 'bg-white' : ''"></span>
                    <span>Hỏng hóc</span>
                    <span class="text-[11px]" x-text="`(${stats.damaged})`"></span>
                </button>
            </div>
        </template>
    </div>

    <!-- Table Container -->
    <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-xs">
        <div
            class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800"
            @scroll.passive="onScroll($event)"
        >
            <!-- VIEW 1: Existing Batch Receiving Table (Matches user mockup) -->
            <template x-if="isEdit && !addMoreMode">
                <table class="w-full text-left text-sm border-collapse">
                    <thead class="sticky top-0 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-xs text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider z-1 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-5 py-3">DÒNG SẢN PHẨM</th>
                            <th class="px-5 py-3">KẾT QUẢ NHẬN HÀNG</th>
                            <th class="px-5 py-3">NGÀY NHẬN</th>
                            <th class="px-5 py-3 text-right">HÀNH ĐỘNG</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="item in assets" :key="item.id">
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-800/60 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm" x-text="item.name"></div>
                                    <div class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5" x-text="item.serial_no"></div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <template x-if="!item.condition">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            Chưa nhận
                                        </span>
                                    </template>
                                    <template x-if="item.condition === 'normal' || item.condition_raw === 'ok'">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Bình thường
                                        </span>
                                    </template>
                                    <template x-if="item.condition === 'damaged' || item.condition_raw === 'fault'">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200/60 dark:border-rose-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Hỏng hóc
                                        </span>
                                    </template>
                                </td>
                                <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 text-sm">
                                    <span x-text="item.received_at || '—'"></span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <template x-if="!item.condition">
                                        <button
                                            type="button"
                                            @click="openReceiveModal(item)"
                                            :disabled="batchStatus === 'completed'"
                                            class="inline-flex items-center justify-center px-3.5 py-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 bg-white dark:bg-gray-800 border border-blue-400 dark:border-blue-500 hover:border-blue-500 rounded-lg shadow-2xs hover:bg-blue-50/50 dark:hover:bg-blue-950/30 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Nhận hàng
                                        </button>
                                    </template>
                                    <template x-if="item.condition">
                                        <button
                                            type="button"
                                            @click="openReceiveModal(item)"
                                            :disabled="batchStatus === 'completed'"
                                            class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-700 rounded-lg transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Sửa
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="assets.length === 0">
                            <td colspan="4" class="px-5 py-8 text-center text-gray-400 text-sm">
                                <span x-show="allAssets.length === 0">Đợt nhập này hiện chưa có thiết bị nào.</span>
                                <span x-show="allAssets.length > 0">Không tìm thấy thiết bị nào khớp với bộ lọc.</span>
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
                        <!-- Skeleton Loader -->
                        <template x-if="loading && assets.length === 0">
                            <template x-for="i in 5" :key="i">
                                <tr class="animate-pulse">
                                    <td class="px-4 py-3.5 text-center">
                                        <div class="w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded mx-auto"></div>
                                    </td>
                                    <td class="px-4 py-3.5"><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-28"></div></td>
                                    <td class="px-4 py-3.5"><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-36"></div></td>
                                    <td class="px-4 py-3.5"><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div></td>
                                    <td class="px-4 py-3.5"><div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-20"></div></td>
                                </tr>
                            </template>
                        </template>

                        <!-- Render assets -->
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

                        <tr x-show="!loading && assets.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                                Không tìm thấy thiết bị nào khớp với từ khóa tìm kiếm.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </template>

            <!-- Loading indicator for infinite scroll -->
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

    <!-- MODAL 1: Nhận hàng -->
    <div
        x-show="showReceiveModal"
        x-cloak
        class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
        @keydown.escape.window="closeReceiveModal()"
    >
        <!-- Backdrop -->
        <div
            x-show="showReceiveModal"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeReceiveModal()"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
        ></div>

        <!-- Modal Dialog -->
        <div
            x-show="showReceiveModal"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 w-full max-w-md p-6 space-y-5 z-10"
            @click.stop
        >
            <!-- Title and Subtitle: {Serial} · {ProductLine} -->
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="activeAsset?.is_received ? 'Cập nhật tình trạng nhận hàng' : 'Nhận hàng'"></h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span class="font-mono font-semibold text-primary-600 dark:text-primary-400" x-text="activeAsset?.serial_no"></span>
                    <span> · </span>
                    <span x-text="activeAsset?.name"></span>
                </p>
            </div>

            <!-- Tình trạng hàng toggle -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Tình trạng hàng</label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        @click="receiveCondition = 'normal'"
                        class="py-3 px-4 rounded-xl border text-sm font-semibold transition-all text-center cursor-pointer flex items-center justify-center gap-2"
                        :class="receiveCondition === 'normal'
                            ? 'border-2 border-emerald-500 bg-emerald-50/50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-300 shadow-xs'
                            : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800'"
                    >
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Bình thường</span>
                    </button>
                    <button
                        type="button"
                        @click="receiveCondition = 'damaged'"
                        class="py-3 px-4 rounded-xl border text-sm font-semibold transition-all text-center cursor-pointer flex items-center justify-center gap-2"
                        :class="receiveCondition === 'damaged'
                            ? 'border-2 border-rose-500 bg-rose-50/50 text-rose-700 dark:border-rose-400 dark:bg-rose-950/40 dark:text-rose-300 shadow-xs'
                            : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800'"
                    >
                        <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Hỏng hóc</span>
                    </button>
                </div>
            </div>

            <!-- Modal Footer: Hủy & Xác nhận nhận hàng -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    @click="closeReceiveModal()"
                    class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg transition-colors cursor-pointer"
                >
                    Hủy
                </button>
                <button
                    type="button"
                    @click="submitReceive()"
                    :disabled="!receiveCondition || submittingReceive"
                    class="px-5 py-2 text-sm font-semibold rounded-lg transition-all inline-flex items-center gap-2"
                    :class="(!receiveCondition || submittingReceive)
                        ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 cursor-not-allowed border border-transparent'
                        : 'bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white shadow-sm cursor-pointer'"
                >
                    <svg x-show="submittingReceive" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="submittingReceive ? 'Đang lưu...' : (activeAsset?.is_received ? 'Cập nhật tình trạng' : 'Xác nhận nhận hàng')"></span>
                </button>
            </div>
        </div>
    </div>



    <!-- Floating Toast Feedback -->
    <template x-teleport="body">
        <div
            x-show="toast.show"
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="fixed bottom-5 right-5 z-[100000] flex items-center gap-2 px-4 py-3 rounded-xl shadow-lg border text-sm font-medium"
            :class="toast.type === 'success'
                ? 'bg-emerald-50 dark:bg-emerald-950/80 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200'
                : 'bg-rose-50 dark:bg-rose-950/80 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200'"
        >
            <svg x-show="toast.type === 'success'" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <svg x-show="toast.type !== 'success'" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
