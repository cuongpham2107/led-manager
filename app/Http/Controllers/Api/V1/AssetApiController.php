<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AssetResource;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetApiController extends Controller
{
    /**
     * Lookup asset by QR Code, Barcode or Serial Number.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = $this->extractCodeFromUrl(trim($request->input('code')));

        $asset = Asset::with(['productLine', 'currentWarehouse', 'statusLogs', 'repairLogs'])
            ->where('serial_no', $code)
            ->orWhere('qr_code', $code)
            ->orWhere('id', is_numeric($code) ? (int) $code : 0)
            ->first();

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy thiết bị nào khớp với mã: '{$code}'.",
            ], 404);
        }

        if ($request->user()->isAgencyScoped()) {
            $whId = $request->user()->getScopedWarehouseId();
            $agencyId = $request->user()->getScopedAgencyId();

            $hasAccess = ($whId && (int) $asset->current_warehouse_id === (int) $whId)
                || ($whId && $asset->statusLogs()->where('to_warehouse_id', $whId)->exists())
                || ($whId && $asset->checkinBatchItems()->whereHas('checkinBatch', fn ($cq) => $cq->where('warehouse_id', $whId))->exists())
                || $asset->checkoutBatchItems()->whereHas('checkoutBatch', function ($cbq) use ($whId, $agencyId) {
                    $cbq->where(function ($q) use ($whId, $agencyId) {
                        if ($whId) {
                            $q->where('warehouse_id', $whId);
                        }
                        if ($agencyId) {
                            $q->orWhereHas('order', fn ($oq) => $oq->where('agency_id', $agencyId));
                        }
                    });
                })->exists()
                || $asset->returnBatchItems()->whereHas('returnBatch.checkoutBatch', function ($cbq) use ($whId, $agencyId) {
                    $cbq->where(function ($q) use ($whId, $agencyId) {
                        if ($whId) {
                            $q->where('warehouse_id', $whId);
                        }
                        if ($agencyId) {
                            $q->orWhereHas('order', fn ($oq) => $oq->where('agency_id', $agencyId));
                        }
                    });
                })->exists();

            if (! $hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiết bị này không thuộc phạm vi quản lý của đại lý.',
                ], 403);
            }
        }

        $recentLogs = $asset->statusLogs()->latest()->take(5)->get();
        $recentRepairs = $asset->repairLogs()->latest()->take(5)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'asset' => new AssetResource($asset),
                'recent_status_logs' => $recentLogs->map(fn ($log) => [
                    'id' => $log->id,
                    'from_status' => $log->from_status?->getLabel(),
                    'to_status' => $log->to_status?->getLabel(),
                    'note' => $log->note,
                    'changed_by' => $log->changedBy?->name,
                    'created_at' => $log->created_at?->toIso8601String(),
                ]),
                'recent_repairs' => $recentRepairs->map(fn ($repair) => [
                    'id' => $repair->id,
                    'repair_note' => $repair->repair_note,
                    'result_status' => $repair->result_status?->getLabel(),
                    'start_date' => $repair->start_date?->format('Y-m-d'),
                    'end_date' => $repair->end_date?->format('Y-m-d'),
                    'cost' => (float) $repair->repair_cost,
                ]),
            ],
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
