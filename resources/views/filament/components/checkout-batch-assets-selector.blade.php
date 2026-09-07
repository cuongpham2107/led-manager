@php
    $statePath = $getStatePath();
@endphp

<div
    wire:key="checkout-assets-selector-{{ $warehouseId ?? 'all' }}-{{ $productLineId ?? 'all' }}-{{ $requiredArea ?? 0 }}"
    x-data="{
        state: $wire.$entangle('{{ $statePath }}'),
        assets: {{ \Illuminate\Support\Js::from($assets ?? []) }},
        requiredArea: {{ (float) ($requiredArea ?? 0) }},
        search: '',

        init() {
            if (!Array.isArray(this.state)) {
                this.state = [];
            }

            const currentIds = this.assets.map(a => Number(a.id));
            if (this.state.length > 0 && currentIds.length > 0) {
                // Retain only selected IDs that belong to the current filtered assets
                this.state = this.state.map(Number).filter(id => currentIds.includes(id));
            }

            if (this.requiredArea > 0 && this.state.length === 0) {
                this.autoSuggestByArea(this.requiredArea);
            }
        },

        get selectedList() {
            return Array.isArray(this.state) ? this.state.map(Number) : [];
        },

        get selectedArea() {
            const selected = this.selectedList;
            return this.assets
                .filter(a => selected.includes(Number(a.id)))
                .reduce((acc, a) => acc + (parseFloat(a.area_m2) || 0.25), 0);
        },

        get filteredAssets() {
            if (!this.search.trim()) return this.assets;
            const term = this.search.toLowerCase().trim();
            return this.assets.filter(a => 
                (a.serial_no && a.serial_no.toLowerCase().includes(term)) ||
                (a.name && a.name.toLowerCase().includes(term)) ||
                (a.size && a.size.toLowerCase().includes(term)) ||
                (a.type && a.type.toLowerCase().includes(term)) ||
                (a.status_label && a.status_label.toLowerCase().includes(term))
            );
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
            }
            this.state = current;
        },

        toggleAll() {
            const visibleIds = this.filteredAssets.map(a => Number(a.id));
            let current = Array.isArray(this.state) ? [...this.state.map(Number)] : [];
            const allChecked = visibleIds.length > 0 && visibleIds.every(id => current.includes(id));

            if (allChecked) {
                this.state = current.filter(id => !visibleIds.includes(id));
            } else {
                const combined = new Set([...current, ...visibleIds]);
                this.state = Array.from(combined);
            }
        },

        autoSuggestByArea(area) {
            if (area <= 0 || this.assets.length === 0) {
                return;
            }

            let accumulated = 0;
            const suggestedIds = [];

            for (const asset of this.assets) {
                suggestedIds.push(Number(asset.id));
                accumulated += (parseFloat(asset.area_m2) || 0.25);
                if (accumulated >= area) {
                    break;
                }
            }

            this.state = suggestedIds;
        }
    }"
    class="space-y-2 relative z-0"
>
    <!-- Header with selection stats and instant search filter -->
    <div class="flex items-center justify-between gap-2 pb-1">
        <div class="text-xs text-gray-500 dark:text-gray-400">
            <span class="font-semibold text-gray-700 dark:text-gray-300" x-text="selectedList.length"></span> / <span x-text="assets.length"></span> thiết bị đã chọn
            <template x-if="selectedArea > 0">
                <span class="font-medium text-primary-600 dark:text-primary-400 ml-1">
                    (Tổng diện tích: <span x-text="selectedArea.toFixed(2)"></span> m²)
                </span>
            </template>
        </div>
        <div class="relative w-48 sm:w-60">
            <input
                type="text"
                x-model="search"
                placeholder="Tìm nhanh theo seri, tên..."
                class="w-full text-xs rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-xs focus:border-primary-500 focus:ring-primary-500 px-2.5 py-1.5 pl-7"
            />
            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    <!-- Table Container with scrollable height -->
    <div class="border border-gray-200 dark:border-gray-700/80 rounded-lg overflow-hidden bg-white dark:bg-gray-900 shadow-xs">
        <div class="max-h-64 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
            <table class="w-full text-left text-sm border-collapse">
                <thead class="sticky top-0 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-xs text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider z-1 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="w-10 px-3 py-2.5 text-center">
                            <input
                                type="checkbox"
                                @click="toggleAll()"
                                :checked="filteredAssets.length > 0 && filteredAssets.every(a => isChecked(a.id))"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-4 h-4 cursor-pointer"
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
                    <template x-for="item in filteredAssets" :key="item.id">
                        <tr
                            @click="toggle(item.id)"
                            class="hover:bg-blue-50/40 dark:hover:bg-gray-800/60 cursor-pointer transition-colors"
                            :class="isChecked(item.id) ? 'bg-blue-50/20 dark:bg-blue-950/20' : ''"
                        >
                            <td class="px-3 py-2.5 text-center" @click.stop>
                                <input
                                    type="checkbox"
                                    :value="item.id"
                                    :checked="isChecked(item.id)"
                                    @change="toggle(item.id)"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-4 h-4 cursor-pointer"
                                />
                            </td>
                            <td class="px-4 py-2.5 font-bold text-gray-900 dark:text-white font-mono text-sm tracking-tight" x-text="item.serial_no"></td>
                            <td class="px-4 py-2.5 text-gray-800 dark:text-gray-200 font-medium" x-text="item.name"></td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400" x-text="item.size"></td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400" x-text="item.type"></td>
                            <td class="px-4 py-2.5">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800': item.status === 'ready',
                                        'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800': item.status === 'in_event',
                                        'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800': item.status === 'in_transit',
                                        'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800': item.status === 'repairing' || item.status === 'missing',
                                        'bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700': !['ready', 'in_event', 'in_transit', 'repairing', 'missing'].includes(item.status),
                                    }"
                                    x-text="item.status_label || 'Sẵn sàng trong kho'"
                                ></span>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty state -->
                    <tr x-show="filteredAssets.length === 0">
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">
                            Không có thiết bị phù hợp với điều kiện lọc (Kho hàng / Loại thiết bị / Tìm kiếm).
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
