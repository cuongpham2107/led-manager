<div class="flex flex-col items-center justify-center p-4 space-y-4 text-center">
    <div class="p-4 bg-white rounded-xl shadow-xs border border-gray-200 inline-block">
        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(220)->generate($record->qr_code ?: $record->serial_no) !!}
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
    </div>
</div>
