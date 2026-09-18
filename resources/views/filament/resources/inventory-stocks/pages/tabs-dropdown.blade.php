@php
    $treeData = $this->getTreeData();
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $scopedWhId = $user?->getScopedWarehouseId();

    $selectedAgencyWarehouse = null;
    if ($this->selectedGroup === 'agency' && $this->selectedWarehouseId) {
        $selectedAgencyWarehouse = collect($treeData['agency_warehouses'])->firstWhere('id', $this->selectedWarehouseId);
    }
@endphp

<div class="mb-4">
    <x-filament::tabs label="Kho hàng và đại lý">
        @if (! $scopedWhId)
            <x-filament::tabs.item
                :active="empty($this->selectedWarehouseId) && empty($this->selectedGroup)"
                :badge="(string) $treeData['grand_total']"
                icon="heroicon-o-squares-2x2"
                wire:click="selectAll"
            >
                Tất cả kho & đại lý
            </x-filament::tabs.item>
        @endif

        {{-- Kho Tổng công ty (HQ) --}}
        @foreach ($treeData['hq_warehouses'] as $wh)
            @php
                $isWhActive = empty($this->selectedGroup) && (int) $this->selectedWarehouseId === (int) $wh['id'];
            @endphp

            <x-filament::tabs.item
                :active="$isWhActive"
                :badge="(string) $wh['total']"
                badge-color="gray"
                icon="heroicon-o-home-modern"
                wire:click="selectWarehouse({{ $wh['id'] }})"
            >
                <span>{{ $wh['name'] }}</span>
            </x-filament::tabs.item>
        @endforeach

        {{-- Nhóm các kho Đại lý --}}
        @if (count($treeData['agency_warehouses']) > 1)
            @php
                $isAgencyActive = $this->selectedGroup === 'agency';
                $agencyBadge = $selectedAgencyWarehouse ? (string) $selectedAgencyWarehouse['total'] : (string) $treeData['agency_total'];
                $agencyLabel = $selectedAgencyWarehouse ? $selectedAgencyWarehouse['name'] : 'Kho Đại lý';
            @endphp

            <x-filament::dropdown placement="bottom-start">
                <x-slot name="trigger">
                    <x-filament::tabs.item
                        :active="$isAgencyActive"
                        :badge="$agencyBadge"
                        badge-color="warning"
                        icon="heroicon-o-building-office-2"
                    >
                        <div class="flex items-center gap-1">
                            <span>{{ $agencyLabel }}</span>
                            <x-heroicon-m-chevron-down class="w-4 h-4 opacity-60" />
                        </div>
                    </x-filament::tabs.item>
                </x-slot>

                <x-filament::dropdown.list>
                    <x-filament::dropdown.list.item
                        wire:click="selectAgencyGroup"
                        :badge="(string) $treeData['agency_total']"
                        badge-color="warning"
                        :color="$isAgencyActive && empty($this->selectedWarehouseId) ? 'primary' : 'gray'"
                        icon="heroicon-o-building-storefront"
                    >
                        Tất cả kho đại lý ({{ count($treeData['agency_warehouses']) }} đại lý)
                    </x-filament::dropdown.list.item>

                    @foreach ($treeData['agency_warehouses'] as $awh)
                        @php
                            $isThisAgencyActive = (int) $this->selectedWarehouseId === (int) $awh['id'];
                        @endphp
                        <x-filament::dropdown.list.item
                            wire:click="selectAgencyWarehouse({{ $awh['id'] }})"
                            :badge="(string) $awh['total']"
                            badge-color="warning"
                            :color="$isThisAgencyActive ? 'primary' : 'gray'"
                            icon="heroicon-o-map-pin"
                        >
                            {{ $awh['name'] }} ({{ $awh['agency_name'] }})
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        @elseif (count($treeData['agency_warehouses']) === 1)
            @php
                $awh = $treeData['agency_warehouses'][0];
                $isAwhActive = (int) $this->selectedWarehouseId === (int) $awh['id'];
            @endphp
            <x-filament::tabs.item
                :active="$isAwhActive"
                :badge="(string) $awh['total']"
                badge-color="warning"
                icon="heroicon-o-building-office-2"
                wire:click="selectAgencyWarehouse({{ $awh['id'] }})"
            >
                <span>{{ $awh['name'] }} ({{ $awh['agency_name'] }})</span>
            </x-filament::tabs.item>
        @endif
    </x-filament::tabs>
</div>
