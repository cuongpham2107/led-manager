<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckinAssetSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $productLineId = $request->query('product_line_id');
        $status = $request->query('status');
        $warehouseId = $request->query('warehouse_id');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(10, (int) $request->query('per_page', 25)));

        $query = Asset::query()
            ->with('productLine')
            ->select(['id', 'serial_no', 'size', 'product_line_id', 'current_status', 'current_warehouse_id']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                    ->orWhere('size', 'like', "%{$search}%")
                    ->orWhereHas('productLine', function ($plQ) use ($search) {
                        $plQ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if (filled($productLineId) && $productLineId !== 'all') {
            $query->where('product_line_id', $productLineId);
        }

        if (filled($status) && $status !== 'all') {
            $query->where('current_status', $status);
        }

        if (filled($warehouseId) && $warehouseId !== 'all') {
            $query->where('current_warehouse_id', $warehouseId);
        }

        $paginator = $query->orderBy('serial_no')->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function (Asset $asset) {
            $statusLabel = $asset->current_status instanceof AssetStatus
                ? $asset->current_status->getLabel()
                : 'Sẵn sàng trong kho';
            $statusColor = $asset->current_status instanceof AssetStatus
                ? $asset->current_status->getColor()
                : 'success';

            return [
                'id' => (int) $asset->id,
                'serial_no' => (string) $asset->serial_no,
                'product_line_id' => (int) $asset->product_line_id,
                'name' => (string) ($asset->productLine?->name ?? 'LED'),
                'size' => (string) ($asset->size ?? '0.5×0.5 m'),
                'status' => (string) $statusLabel,
                'status_raw' => (string) ($asset->current_status instanceof AssetStatus ? $asset->current_status->value : $asset->current_status),
                'status_color' => (string) $statusColor,
            ];
        });

        return response()->json([
            'items' => $items,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'has_more' => $paginator->hasMorePages(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * Nhận hàng cho 1 thiết bị cụ thể trong đợt nhập.
     */
    public function receiveItem(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => ['required', 'integer', 'exists:checkin_batches,id'],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'condition' => ['required', 'string', 'in:normal,damaged,ok,fault'],
        ]);

        $batch = CheckinBatch::findOrFail($request->input('batch_id'));
        $asset = Asset::with('productLine')->findOrFail($request->input('asset_id'));

        $conditionInput = $request->input('condition');
        $dbCondition = in_array($conditionInput, ['damaged', 'fault'], true) ? 'fault' : 'ok';
        $targetAssetStatus = $dbCondition === 'fault' ? AssetStatus::Repairing : AssetStatus::Ready;

        $now = now();
        $user = auth()->user();

        return DB::transaction(function () use ($batch, $asset, $dbCondition, $targetAssetStatus, $now, $user) {
            $item = CheckinBatchItem::firstOrNew([
                'checkin_batch_id' => $batch->id,
                'asset_id' => $asset->id,
            ]);

            $item->is_received = true;
            $item->condition = $dbCondition;
            $item->received_at = $now;
            $item->received_by = $user?->id;
            $item->save();

            $oldStatus = $asset->current_status;
            $oldWarehouseId = $asset->current_warehouse_id;

            $asset->update([
                'current_status' => $targetAssetStatus,
                'current_warehouse_id' => $batch->warehouse_id,
            ]);

            AssetStatusLog::create([
                'asset_id' => $asset->id,
                'from_status' => $oldStatus,
                'to_status' => $targetAssetStatus,
                'from_warehouse_id' => $oldWarehouseId,
                'to_warehouse_id' => $batch->warehouse_id,
                'source_type' => CheckinBatch::class,
                'source_id' => $batch->id,
                'changed_by' => $user?->id,
                'note' => 'Nhận hàng thiết bị đợt: '.$batch->code.($dbCondition === 'fault' ? ' (Hỏng hóc)' : ' (Bình thường)'),
                'created_at' => $now,
            ]);

            if ($batch->status === BatchStatus::Pending) {
                $batch->update(['status' => BatchStatus::InProgress]);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã nhận hàng thiết bị {$asset->serial_no}",
                'item' => [
                    'id' => (int) $asset->id,
                    'item_id' => (int) $item->id,
                    'serial_no' => (string) $asset->serial_no,
                    'name' => (string) ($asset->productLine?->name ?? 'LED'),
                    'is_received' => true,
                    'condition' => $dbCondition === 'fault' ? 'damaged' : 'normal',
                    'condition_raw' => $dbCondition,
                    'received_at' => $now->format('d/m/Y H:i'),
                ],
            ]);
        });
    }

    /**
     * Kết thúc nhận hàng cho toàn bộ đợt nhập.
     */
    public function completeBatch(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => ['required', 'integer', 'exists:checkin_batches,id'],
        ]);

        $batch = CheckinBatch::with('items.asset')->findOrFail($request->input('batch_id'));
        $batch->complete(auth()->user());

        return response()->json([
            'success' => true,
            'message' => "Đợt nhập kho {$batch->code} đã kết thúc nhận hàng hoàn tất!",
        ]);
    }
}
