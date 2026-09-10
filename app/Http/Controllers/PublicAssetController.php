<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\View\View;

class PublicAssetController extends Controller
{
    /**
     * Display the public asset inspection page.
     *
     * Accessible without authentication — designed for QR code scanning.
     */
    public function show(string $code): View
    {
        $code = $this->extractCodeFromUrl(trim($code));

        $asset = Asset::with([
            'productLine',
            'currentWarehouse',
            'warehouseLocation',
            'checkoutBatchItems.checkoutBatch.order.customer',
            'checkoutBatchItems.checkoutBatch.warehouse',
            'checkoutBatchItems.returnBatchItem',
            'repairLogs.creator',
        ])
            ->where('serial_no', $code)
            ->orWhere('qr_code', $code)
            ->orWhere('id', is_numeric($code) ? (int) $code : 0)
            ->first();

        if (! $asset) {
            return view('public.asset-detail', [
                'asset' => null,
                'code' => $code,
            ]);
        }

        $history = $asset->checkoutBatchItems()
            ->with([
                'checkoutBatch.order.customer',
                'checkoutBatch.warehouse',
                'returnBatchItem',
            ])
            ->latest('id')
            ->get();

        $repairLogs = $asset->repairLogs()
            ->with('creator')
            ->latest('id')
            ->get();

        $eventsCount = $history->count();
        $normalReturns = $history->filter(
            fn ($i) => $i->returnBatchItem?->grade?->value === 'normal'
        )->count();
        $totalReturned = $history->filter(
            fn ($i) => (bool) $i->returnBatchItem?->is_received
        )->count();
        $qualityRate = $totalReturned > 0
            ? round(($normalReturns / $totalReturned) * 100)
            : null;

        return view('public.asset-detail', [
            'asset' => $asset,
            'code' => $code,
            'history' => $history,
            'repairLogs' => $repairLogs,
            'eventsCount' => $eventsCount,
            'qualityRate' => $qualityRate,
        ]);
    }

    /**
     * If the scanned code is a full URL (e.g. https://domain/q/LED-001),
     * extract just the asset code segment.
     */
    private function extractCodeFromUrl(string $code): string
    {
        if (preg_match('#/q/([^/?]+)#i', $code, $matches)) {
            return $matches[1];
        }

        return $code;
    }
}
