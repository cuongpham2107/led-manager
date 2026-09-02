<div class="space-y-6">
    {{-- Print header --}}
    <div class="flex items-center justify-between print:hidden">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                Mã QR đợt nhập kho: {{ $batch->code }}
            </h2>
            <p class="text-sm text-gray-500">
                {{ $batch->items->count() }} thiết bị
                • Loại / Dòng SP: {{ $batch->productLine?->name ?? $batch->deviceType?->name ?? 'Thiết bị' }}
                • Kho: {{ $batch->warehouse?->name ?? 'N/A' }}
            </p>
        </div>
        <button
            onclick="window.print()"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition"
        >
            <x-heroicon-m-printer class="w-4 h-4" />
            In tất cả QR
        </button>
    </div>

    {{-- QR Grid (printable) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 print:grid-cols-4 print:gap-2">
        @foreach ($batch->items()->with(['asset.productLine', 'asset.deviceType'])->get() as $item)
            @if ($item->asset)
                <div class="flex flex-col items-center p-3 bg-white rounded-lg border border-gray-200 text-center break-inside-avoid print:border print:p-2 print:rounded-none">
                    <div class="p-2 bg-white">
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->margin(1)->generate($item->asset->qr_code ?: $item->asset->serial_no) !!}
                    </div>
                    <div class="mt-1 text-xs font-bold text-gray-900 font-mono tracking-wider">
                        {{ $item->asset->serial_no }}
                    </div>
                    <div class="text-[10px] text-gray-500 leading-tight">
                        {{ $item->asset->productLine?->name ?? $item->asset->deviceType?->name ?? 'Thiết bị' }}
                        @if ($item->asset->size)
                            • {{ $item->asset->size }}
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>

{{-- Print styles --}}
<style>
    @media print {
        /* Hide Filament chrome when printing */
        .fi-sidebar, .fi-topbar, .fi-header, .fi-breadcrumbs,
        .fi-page-header-actions, .fi-footer,
        .fi-section:not(.qr-print-section),
        nav, header, footer { display: none !important; }

        .fi-main { padding: 0 !important; margin: 0 !important; }

        body { background: white !important; }

        @page {
            size: A4;
            margin: 10mm;
        }
    }
</style>
