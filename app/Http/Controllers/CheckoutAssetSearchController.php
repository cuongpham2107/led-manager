<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        if (! empty($warehouseId)) {
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
}
