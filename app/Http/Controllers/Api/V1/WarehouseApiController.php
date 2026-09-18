<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseApiController extends Controller
{
    /**
     * List all active warehouses.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::withCount('assets')
            ->where('is_active', true);

        if ($request->user()->isAgencyScoped()) {
            $scopedWarehouseId = $request->user()->getScopedWarehouseId();
            $query->where('id', $scopedWarehouseId);
        }

        $warehouses = $query->get();

        return response()->json([
            'success' => true,
            'data' => WarehouseResource::collection($warehouses),
        ]);
    }
}
