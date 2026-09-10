<div class="flex flex-col items-center justify-center p-4 space-y-4 text-center">
    @php
        $qrContent = route('asset.public.show', ['code' => $record->qr_code ?: $record->serial_no]);
    @endphp
    <div class="p-4 bg-white rounded-xl shadow-xs border border-gray-200 inline-block">
        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(220)->generate($qrContent) !!}
    </div>

    <div class="space-y-1">
        <div class="text-base font-extrabold text-gray-900 dark:text-white font-mono">
            {{ $record->serial_no }}
        </div>
        <div class="text-xs text-gray-500">
            Dòng: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $record->productLine?->name ?? 'N/A' }}</span>
            • Quy cách: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $record->size ?? '0.5×0.5 m' }}</span>
        </div>
        <div class="text-xs text-gray-500">
            Kho: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $record->currentWarehouse?->name ?? 'N/A' }}</span>
            • Mã QR: <span class="font-mono text-primary-600">{{ $record->qr_code ?: $record->serial_no }}</span>
        </div>
        <a href="{{ $qrContent }}" target="_blank" class="inline-flex items-center gap-1.5 mt-2 text-xs font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-200 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            Xem trang thông tin công khai ↗
        </a>
    </div>
</div>
