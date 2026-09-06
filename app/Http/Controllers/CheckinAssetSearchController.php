<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinAssetSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(10, (int) $request->query('per_page', 25)));

        $query = Asset::query()
            ->with('productLine')
            ->select(['id', 'serial_no', 'size', 'product_line_id', 'current_status']);

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
            $statusLabel = $asset->current_status instanceof AssetStatus
                ? $asset->current_status->getLabel()
                : 'Sẵn sàng trong kho';
            $statusColor = $asset->current_status instanceof AssetStatus
                ? $asset->current_status->getColor()
                : 'success';

            return [
                'id' => (int) $asset->id,
                'serial_no' => (string) $asset->serial_no,
                'name' => (string) ($asset->productLine?->name ?? 'LED'),
                'size' => (string) ($asset->size ?? '0.5×0.5 m'),
                'status' => (string) $statusLabel,
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
}
