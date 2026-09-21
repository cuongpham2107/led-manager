<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutAssetSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $warehouseId = $request->query('warehouse_id');
        $productLineId = $request->query('product_line_id');
        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(200, max(10, (int) $request->query('per_page', 50)));

        $query = Asset::query()
            ->with('productLine')
            ->where('current_status', AssetStatus::Ready)
            ->whereDoesntHave('checkoutBatchItems', function ($q) {
                $q->whereHas('checkoutBatch', function ($b) {
                    $b->whereNotIn('status', [BatchStatus::Completed, BatchStatus::Cancelled]);
                });
            });

        /** @var User|null $user */
        $user = Auth::user();
        if ($scopedWhId = $user?->getScopedWarehouseId()) {
            $query->where('current_warehouse_id', $scopedWhId);
        } elseif (! empty($warehouseId)) {
            $query->where('current_warehouse_id', $warehouseId);
        }

        if (! empty($productLineId)) {
            $query->where('product_line_id', $productLineId);
        }

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

        $paginator = $query->orderBy('serial_no')->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function (Asset $asset) {
            $pl = $asset->productLine;
            $fullName = $pl?->name ?? 'Cabin LED';
            $code = $pl?->code ?: '';

            // Derive TÊN THIẾT BỊ (e.g. P2.9, P1.5, P2.6)
            $shortName = $code ? preg_replace('/-FIX$/', '', $code) : explode(' ', $fullName)[0];

            // Derive LOẠI THIẾT BỊ (e.g. Sự kiện, Trong nhà cố định, Ngoài trời)
            if (str_contains($fullName, 'Sự kiện')) {
                $type = 'Sự kiện';
            } elseif (str_contains($fullName, 'Trong nhà cố định')) {
                $type = 'Trong nhà cố định';
            } elseif (str_contains($fullName, 'Outdoor') || str_contains($fullName, 'Ngoài trời')) {
                $type = 'Ngoài trời';
            } else {
                $type = $pl?->environment?->getLabel() ?? 'Sự kiện';
            }

            // Calculate cabinet area
            $area = 0.25;
            if ($pl && (float) $pl->module_width_mm > 0 && (float) $pl->module_height_mm > 0) {
                $area = ((float) $pl->module_width_mm / 1000) * ((float) $pl->module_height_mm / 1000);
            } elseif (! empty($asset->size)) {
                if (str_contains($asset->size, '0.5×1') || str_contains($asset->size, '0.5x1')) {
                    $area = 0.5;
                } elseif (str_contains($asset->size, '0.5×0.5') || str_contains($asset->size, '0.5x0.5')) {
                    $area = 0.25;
                }
            }

            return [
                'id' => (int) $asset->id,
                'serial_no' => (string) $asset->serial_no,
                'name' => (string) $shortName,
                'size' => (string) ($asset->size ?? '0.5×0.5 m'),
                'type' => (string) $type,
                'status' => (string) $asset->current_status->value,
                'status_label' => (string) $asset->current_status->getLabel(),
                'status_color' => (string) $asset->current_status->getColor(),
                'area_m2' => (float) $area,
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
     * Đánh dấu xuất kho cho 1 thiết bị cụ thể trong đợt xuất.
     */
    public function dispatchItem(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => ['required', 'integer', 'exists:checkout_batches,id'],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
        ]);

        $batch = CheckoutBatch::findOrFail($request->input('batch_id'));

        if (in_array($batch->status, [BatchStatus::Dispatched, BatchStatus::Completed, BatchStatus::Cancelled])) {
            return response()->json([
                'success' => false,
                'message' => 'Đợt xuất này đã được xác nhận hoặc đã hủy, không thể đánh dấu thêm.',
            ], 422);
        }

        $asset = Asset::with('productLine')->findOrFail($request->input('asset_id'));

        $now = now();
        $user = Auth::user();

        return DB::transaction(function () use ($batch, $asset, $now, $user) {
            $item = CheckoutBatchItem::where('checkout_batch_id', $batch->id)
                ->where('asset_id', $asset->id)
                ->first();

            if (! $item) {
                $item = CheckoutBatchItem::create([
                    'checkout_batch_id' => $batch->id,
                    'asset_id' => $asset->id,
                    'is_dispatched' => false,
                    'note' => $batch->note,
                ]);
            }

            if ($item->is_dispatched) {
                return response()->json([
                    'success' => false,
                    'message' => "Thiết bị {$asset->serial_no} đã được xuất kho trước đó.",
                ], 422);
            }

            $item->update([
                'is_dispatched' => true,
                'dispatched_by' => $user?->id,
                'dispatched_at' => $now,
            ]);

            $oldStatus = $asset->current_status;
            $asset->update(['current_status' => AssetStatus::InTransit]);

            AssetStatusLog::create([
                'asset_id' => $asset->id,
                'from_status' => $oldStatus,
                'to_status' => AssetStatus::InTransit,
                'from_warehouse_id' => $asset->current_warehouse_id,
                'to_warehouse_id' => $batch->warehouse_id,
                'source_type' => CheckoutBatch::class,
                'source_id' => $batch->id,
                'changed_by' => $user?->id,
                'note' => "Xuất kho thiết bị đợt {$batch->code}",
                'created_at' => $now,
            ]);

            if ($batch->status === BatchStatus::Pending) {
                $batch->update(['status' => BatchStatus::InProgress]);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã xuất kho thiết bị {$asset->serial_no}",
                'item' => [
                    'id' => (int) $asset->id,
                    'item_id' => (int) $item->id,
                    'serial_no' => (string) $asset->serial_no,
                    'is_dispatched' => true,
                    'dispatched_at' => $now->format('d/m/Y H:i'),
                ],
            ]);
        });
    }
}
