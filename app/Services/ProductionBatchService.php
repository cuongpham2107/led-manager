<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\DeviceType;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductionBatchService
{
    /**
     * Create a check-in batch from a finished production run, atomically:
     *  - 1 CheckinBatch (batch_type = production, status = completed)
     *  - N Asset records (status = Ready, in the chosen warehouse)
     *  - N CheckinBatchItem rows (is_received = true)
     *  - N AssetStatusLog rows (transition into Ready)
     *
     * Supports both LED modules/cabinets (with ProductLine) and peripheral equipment
     * (Processors, Sending cards, Truss, Cables, Flycases) where ProductLine may be null.
     *
     * @return array{batch: CheckinBatch, assets: Collection<int, Asset>}
     *
     * @throws \RuntimeException when any generated serial_no already exists
     */
    public function createFromProduction(
        ?ProductLine $productLine,
        DeviceType $deviceType,
        int $quantity,
        Warehouse $warehouse,
        ?string $size,
        string $serialPrefix,
        ?string $note,
        ?User $createdBy = null,
    ): array {
        if ($quantity < 1 || $quantity > 500) {
            throw new \InvalidArgumentException('Số lượng sản xuất phải từ 1 đến 500.');
        }

        return DB::transaction(function () use ($productLine, $deviceType, $quantity, $warehouse, $size, $serialPrefix, $note, $createdBy) {
            $batchCode = CodeGeneratorService::generate('IN', 'checkin_batches');
            $now = now();

            $batch = CheckinBatch::create([
                'code' => $batchCode,
                'warehouse_id' => $warehouse->id,
                'batch_type' => CheckinBatchType::Production,
                'product_line_id' => $productLine?->id,
                'device_type_id' => $deviceType->id,
                'quantity' => $quantity,
                'production_note' => $note,
                'status' => BatchStatus::Completed,
                'created_by' => $createdBy?->id,
                'completed_at' => $now,
            ]);

            $assets = collect();
            $paddedWidth = max(3, strlen((string) $quantity));

            for ($i = 1; $i <= $quantity; $i++) {
                $serial = $this->makeSerial($serialPrefix, $i, $paddedWidth);

                if (Asset::where('serial_no', $serial)->orWhere('qr_code', $serial)->exists()) {
                    throw new \RuntimeException("Serial '{$serial}' đã tồn tại trong hệ thống. Vui lòng đổi prefix hoặc kiểm tra lại.");
                }

                $asset = Asset::create([
                    'serial_no' => $serial,
                    'qr_code' => $serial,
                    'product_line_id' => $productLine?->id,
                    'device_type_id' => $deviceType->id,
                    'size' => $size,
                    'manufactured_date' => $now->toDateString(),
                    'purchase_cost' => 0,
                    'current_status' => AssetStatus::Ready,
                    'current_warehouse_id' => $warehouse->id,
                    'note' => $note ? "Nhập từ đợt sản xuất [{$batch->code}]: {$note}" : "Nhập từ đợt sản xuất [{$batch->code}]",
                ]);
                $assets->push($asset);

                CheckinBatchItem::create([
                    'checkin_batch_id' => $batch->id,
                    'asset_id' => $asset->id,
                    'condition' => 'ok',
                    'is_received' => true,
                    'received_by' => $createdBy?->id,
                    'received_at' => $now,
                ]);

                AssetStatusLog::create([
                    'asset_id' => $asset->id,
                    'from_status' => null,
                    'to_status' => AssetStatus::Ready,
                    'from_warehouse_id' => null,
                    'to_warehouse_id' => $warehouse->id,
                    'source_type' => CheckinBatch::class,
                    'source_id' => $batch->id,
                    'changed_by' => $createdBy?->id,
                    'note' => "Nhập kho từ đợt sản xuất [{$batch->code}]",
                    'created_at' => $now,
                ]);
            }

            return ['batch' => $batch, 'assets' => $assets];
        });
    }

    /**
     * Build a serial number from the supplied prefix and sequence index.
     */
    public function makeSerial(string $prefix, int $index, int $paddedWidth = 3): string
    {
        $prefix = trim($prefix) !== '' ? trim($prefix) : 'ASSET';
        $index = max(1, $index);

        return $prefix.'-'.str_pad((string) $index, $paddedWidth, '0', STR_PAD_LEFT);
    }

    /**
     * Serial prefix suggestion: {ProductLine.code|DeviceType.code}-{YYMMDD}
     */
    public function suggestSerialPrefix(?ProductLine $productLine = null, ?DeviceType $deviceType = null, ?Carbon $date = null): string
    {
        $date ??= now();
        $code = $productLine?->code ?: ($deviceType?->code ?: 'ASSET');

        return Str::upper($code).'-'.$date->format('ymd');
    }
}
