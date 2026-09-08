@php
    $treeData = $this->getTreeData();
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $scopedWhId = $user?->getScopedWarehouseId();
@endphp

<div class="mb-4">
    <x-filament::tabs label="Kho hàng và vị trí">
        @if (! $scopedWhId)
            <x-filament::tabs.item
                :active="empty($this->selectedWarehouseId) && empty($this->selectedLocationId)"
                :badge="(string) $treeData['grand_total']"
                icon="heroicon-o-squares-2x2"
                wire:click="selectAll"
            >
                Tất cả thiết bị
            </x-filament::tabs.item>
        @endif

        @foreach ($treeData['warehouses'] as $wh)
            @php
                $isWhActive = (int) $this->selectedWarehouseId === (int) $wh['id'];
                $hasLocations = count($wh['locations']) > 0 || $wh['unassigned_count'] > 0;
                $selectedLocName = null;
                if ($isWhActive && $this->selectedLocationId) {
                    $selectedLocName = $this->selectedLocationId === 'unassigned'
                        ? 'Chưa xếp vị trí'
                        : (collect($wh['locations'])->firstWhere('id', (int) $this->selectedLocationId)['name'] ?? null);
                }
            @endphp

            @if ($hasLocations)
                <x-filament::dropdown placement="bottom-start">
                    <x-slot name="trigger">
                        <x-filament::tabs.item
                            :active="$isWhActive"
                            :badge="(string) $wh['total']"
                            icon="heroicon-m-chevron-down"
                            icon-position="after"
                        >
                            <span>{{ $wh['name'] }}</span>
                            @if ($selectedLocName)
                                <span class="text-xs font-normal opacity-75">({{ $selectedLocName }})</span>
                            @endif
                        </x-filament::tabs.item>
                    </x-slot>

                    <x-filament::dropdown.list>
                        <x-filament::dropdown.list.item
                            wire:click="selectWarehouse({{ $wh['id'] }})"
                            :badge="(string) $wh['total']"
                            :color="$isWhActive && empty($this->selectedLocationId) ? 'primary' : 'gray'"
                            :badge-color="$isWhActive && empty($this->selectedLocationId) ? 'primary' : 'gray'"
                            icon="heroicon-o-building-storefront"
                        >
                            Tất cả tại {{ $wh['name'] }}
                        </x-filament::dropdown.list.item>

                        @foreach ($wh['locations'] as $loc)
                            @php
                                $isLocActive = $isWhActive && (string) $this->selectedLocationId === (string) $loc['id'];
                            @endphp
                            <x-filament::dropdown.list.item
                                wire:click="selectLocation({{ $wh['id'] }}, '{{ $loc['id'] }}')"
                                :badge="(string) $loc['count']"
                                :color="$isLocActive ? 'primary' : 'gray'"
                                :badge-color="$isLocActive ? 'primary' : 'gray'"
                                icon="heroicon-o-map-pin"
                            >
                                {{ $loc['name'] }}
                            </x-filament::dropdown.list.item>
                        @endforeach

                        @if ($wh['unassigned_count'] > 0)
                            @php
                                $isUnassignedActive = $isWhActive && $this->selectedLocationId === 'unassigned';
                            @endphp
                            <x-filament::dropdown.list.item
                                wire:click="selectLocation({{ $wh['id'] }}, 'unassigned')"
                                :badge="(string) $wh['unassigned_count']"
                                :badge-color="$isUnassignedActive ? 'primary' : 'warning'"
                                :color="$isUnassignedActive ? 'primary' : 'gray'"
                                icon="heroicon-o-question-mark-circle"
                            >
                                Chưa xếp vị trí
                            </x-filament::dropdown.list.item>
                        @endif
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @else
                <x-filament::tabs.item
                    :active="$isWhActive && empty($this->selectedLocationId)"
                    :badge="(string) $wh['total']"
                    icon="heroicon-o-building-storefront"
                    wire:click="selectWarehouse({{ $wh['id'] }})"
                >
                    {{ $wh['name'] }}
                </x-filament::tabs.item>
            @endif
        @endforeach
    </x-filament::tabs>
</div>
