<div
    x-data="{
        search: '',
        allAssets: {{ \Illuminate\Support\Js::from($initialAssets ?? []) }},
        assets: {{ \Illuminate\Support\Js::from($initialAssets ?? []) }},
        batchId: {{ !empty($batchId) ? (int) $batchId : 'null' }},
        batchStatus: '{{ $batchStatus ?? 'pending' }}',
        receiveUrl: '{{ $receiveUrl ?? route('filament.return-receive-item') }}',
        completeUrl: '{{ $completeUrl ?? route('filament.return-complete-batch') }}',
        csrfToken: '{{ csrf_token() }}',

        showReceiveModal: false,
        activeAsset: null,
        receiveCondition: null,
        receiveNote: '',
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
            this.assets = [...this.allAssets];
        },

        get stats() {
            const total = this.allAssets.length;
            const received = this.allAssets.filter(a => a.is_received).length;
            const normal = this.allAssets.filter(a => a.is_received && (a.condition === 'normal' || a.condition_raw === 'normal')).length;
            const damaged = this.allAssets.filter(a => a.is_received && (a.condition === 'damaged' || a.condition_raw === 'damaged')).length;
            const unreceived = total - received;

            return { total, received, normal, damaged, unreceived };
        },

        onSearchInput() {
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
        },

        clearSearch() {
            this.search = '';
            this.assets = [...this.allAssets];
        },

        openReceiveModal(item) {
            this.activeAsset = item;
            this.receiveCondition = item.condition || 'normal';
            this.receiveNote = item.grade_note || '';
            this.showReceiveModal = true;
        },

        closeReceiveModal() {
            this.showReceiveModal = false;
            this.activeAsset = null;
            this.receiveCondition = null;
            this.receiveNote = '';
            this.submittingReceive = false;
        },

        async submitReceive() {
            if (!this.activeAsset || !this.receiveCondition || this.submittingReceive) return;

            this.submittingReceive = true;
            try {
                const response = await fetch(this.receiveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        batch_id: this.batchId,
                        asset_id: this.activeAsset.id,
                        condition: this.receiveCondition,
                        note: this.receiveNote,
                    }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    const target = this.allAssets.find(a => a.id === this.activeAsset.id);
                    if (target) {
                        target.is_received = true;
                        target.condition = data.item.condition;
                        target.condition_raw = data.item.condition_raw;
                        target.received_at = data.item.received_at;
                        target.grade_note = this.receiveNote;
                    }

                    const visibleTarget = this.assets.find(a => a.id === this.activeAsset.id);
                    if (visibleTarget) {
                        visibleTarget.is_received = true;
                        visibleTarget.condition = data.item.condition;
                        visibleTarget.condition_raw = data.item.condition_raw;
                        visibleTarget.received_at = data.item.received_at;
                        visibleTarget.grade_note = this.receiveNote;
                    }

                    const isUpdate = Boolean(this.activeAsset.condition);
                    const condText = this.receiveCondition === 'damaged' ? 'Hỏng hóc' : 'Bình thường';
                    this.showToast(`${isUpdate ? 'Đã cập nhật' : 'Đã nhận hàng'}: ${this.activeAsset.serial_no} (${condText})`);
                    this.closeReceiveModal();
                } else {
                    this.showToast(data.message || 'Không thể lưu kết quả nhận hàng.', 'error');
                }
            } catch (err) {
                console.error('Receive item error:', err);
                this.showToast('Lỗi kết nối máy chủ khi nhận hàng.', 'error');
            } finally {
                this.submittingReceive = false;
            }
        },
    }"
    class="space-y-4 font-sans text-gray-900 dark:text-gray-100"
>
    <!-- Top Bar: Search & Stats -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-gray-900 p-3.5 rounded-xl border border-gray-200/80 dark:border-gray-800 shadow-2xs">
        <!-- Search Input -->
        <div class="relative w-full sm:w-80">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input
                type="text"
                x-model="search"
                @input="onSearchInput()"
                placeholder="Tìm theo Serial, tên thiết bị..."
                class="w-full pl-9 pr-8 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800 focus:bg-white dark:focus:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
            >
            <button
                x-show="search.length > 0"
                type="button"
                @click="clearSearch()"
                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Progress Badges Summary -->
        <div class="flex items-center gap-2 flex-wrap text-xs">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium">
                <span>Tổng:</span>
                <strong x-text="stats.total"></strong>
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 font-medium border border-emerald-200/50 dark:border-emerald-800/50">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Bình thường:</span>
                <strong x-text="stats.normal"></strong>
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 font-medium border border-rose-200/50 dark:border-rose-800/50">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                <span>Hỏng hóc:</span>
                <strong x-text="stats.damaged"></strong>
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 font-medium border border-amber-200/50 dark:border-amber-800/50">
                <span>Chưa nhận:</span>
                <strong x-text="stats.unreceived"></strong>
            </span>
        </div>
    </div>

    <!-- Table Container -->
    <div class="relative bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800 shadow-xs overflow-hidden">
        <div class="max-h-[480px] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
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
                            <!-- Product line & Serial -->
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-gray-900 dark:text-white text-sm" x-text="item.name"></div>
                                <div class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-0.5" x-text="item.serial_no"></div>
                            </td>

                            <!-- Condition badge -->
                            <td class="px-5 py-3.5">
                                <template x-if="!item.condition">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        Chưa nhận
                                    </span>
                                </template>
                                <template x-if="item.condition === 'normal' || item.condition_raw === 'normal'">
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Bình thường
                                        </span>
                                        <div x-show="item.grade_note" class="text-xs text-gray-500 dark:text-gray-400 italic" x-text="item.grade_note"></div>
                                    </div>
                                </template>
                                <template x-if="item.condition === 'damaged' || item.condition_raw === 'damaged'">
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200/60 dark:border-rose-800/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Hỏng hóc
                                        </span>
                                        <div x-show="item.grade_note" class="text-xs text-rose-600 dark:text-rose-400 italic" x-text="item.grade_note"></div>
                                    </div>
                                </template>
                            </td>

                            <!-- Received date -->
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 text-sm">
                                <span x-text="item.received_at || '—'"></span>
                            </td>

                            <!-- Action: Nhận hàng / Sửa -->
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
                            <span x-show="allAssets.length === 0">Đợt trả này hiện chưa có thiết bị nào.</span>
                            <span x-show="allAssets.length > 0">Không tìm thấy thiết bị nào khớp với từ khóa tìm kiếm.</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- POPUP MODAL: Receive Single Asset (Kept within component to preserve input focus) -->
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
            <!-- Title and Subtitle -->
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="activeAsset?.condition ? 'Cập nhật tình trạng thiết bị' : 'Nhận hàng hoàn trả'"></h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span class="font-mono font-semibold text-primary-600 dark:text-primary-400" x-text="activeAsset?.serial_no"></span>
                    <span> · </span>
                    <span x-text="activeAsset?.name"></span>
                </p>
            </div>

            <!-- Tình trạng hàng toggle -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Tình trạng thiết bị</label>
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

            <!-- Note field -->
            <div x-show="receiveCondition" x-transition class="space-y-1.5">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                    <span x-show="receiveCondition === 'damaged'" class="text-rose-600 dark:text-rose-400">Mô tả tình trạng lỗi / hỏng</span>
                    <span x-show="receiveCondition !== 'damaged'">Ghi chú (tùy chọn)</span>
                </label>
                <textarea
                    x-model="receiveNote"
                    rows="2"
                    :placeholder="receiveCondition === 'damaged' ? 'VD: Chết 2 module LED góc dưới, móp khung nhôm...' : 'Ghi chú thêm về thiết bị (nếu có)...'"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                    :class="receiveCondition === 'damaged' ? 'focus:ring-rose-500/20 focus:border-rose-500 border-rose-300 dark:border-rose-700' : ''"
                ></textarea>
            </div>

            <!-- Modal Footer -->
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
                    <span x-text="submittingReceive ? 'Đang lưu...' : (activeAsset?.condition ? 'Lưu thay đổi' : 'Xác nhận nhận hàng')"></span>
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
