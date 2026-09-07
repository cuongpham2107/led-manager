<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Carbon\Carbon;
use DateTimeInterface;
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
     * @return array{batch: CheckinBatch, assets: Collection<int, Asset>}
     *
     * @throws \RuntimeException when any generated serial_no already exists
     */
    public function createFromProduction(
        ?ProductLine $productLine,
        int $quantity,
        Warehouse $warehouse,
        ?string $size,
        string $serialPrefix,
        ?string $note,
        ?User $createdBy = null,
        WarehouseLocation|int|null $warehouseLocation = null,
    ): array {
        if ($quantity < 1 || $quantity > 500) {
            throw new \InvalidArgumentException('Số lượng sản xuất phải từ 1 đến 500.');
        }

        $warehouseLocationId = $warehouseLocation instanceof WarehouseLocation
            ? $warehouseLocation->id
            : ($warehouseLocation ? (int) $warehouseLocation : null);

        return DB::transaction(function () use ($productLine, $quantity, $warehouse, $size, $serialPrefix, $note, $createdBy, $warehouseLocationId) {
            $batchCode = CodeGeneratorService::generate('IN', 'checkin_batches');
            $now = now();

            $batch = CheckinBatch::create([
                'code' => $batchCode,
                'warehouse_id' => $warehouse->id,
                'batch_type' => CheckinBatchType::Production,
                'product_line_id' => $productLine?->id,
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
                    'size' => $size,
                    'manufactured_date' => $now->toDateString(),
                    'purchase_cost' => 0,
                    'current_status' => AssetStatus::Ready,
                    'current_warehouse_id' => $warehouse->id,
                    'warehouse_location_id' => $warehouseLocationId,
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
     * Serial prefix suggestion: {ProductLine.code}-YYMMDD
     */
    public function suggestSerialPrefix(?ProductLine $productLine = null, ?Carbon $date = null): string
    {
        $date ??= now();
        $code = $productLine?->code ?: 'ASSET';

        return Str::upper($code).'-'.$date->format('ymd');
    }

    /**
     * Create a check-in batch from spreadsheet rows (UniverSheet import) atomically:
     *  - 1 CheckinBatch (batch_type = production, status = completed)
     *  - N Asset records created or updated (status = Ready, in the specified warehouse)
     *  - N CheckinBatchItem rows (is_received = true)
     *  - N AssetStatusLog rows (transition into Ready)
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, int>  $columnMap
     * @return array{batch: CheckinBatch, created_count: int, updated_count: int, skipped_count: int, assets: Collection<int, Asset>}
     */
    public function createFromSpreadsheet(
        array $rows,
        array $columnMap,
        Warehouse $warehouse,
        ?ProductLine $defaultProductLine = null,
        WarehouseLocation|int|null $defaultLocation = null,
        ?string $productionNote = null,
        bool $updateExisting = true,
        ?User $createdBy = null,
        DateTimeInterface|string|null $expectedDate = null,
    ): array {
        if (! isset($columnMap['serial_no'])) {
            throw new \InvalidArgumentException('Dữ liệu bảng tính thiếu cột Số Seri.');
        }

        $defaultLocationId = $defaultLocation instanceof WarehouseLocation
            ? $defaultLocation->id
            : ($defaultLocation ? (int) $defaultLocation : null);

        return DB::transaction(function () use (
            $rows,
            $columnMap,
            $warehouse,
            $defaultProductLine,
            $defaultLocationId,
            $productionNote,
            $updateExisting,
            $createdBy,
            $expectedDate
        ) {
            $batchCode = CodeGeneratorService::generate('IN', 'checkin_batches');
            $now = now();
            $parsedExpectedDate = $expectedDate ? $this->parseDate($expectedDate) : null;

            $batch = CheckinBatch::create([
                'code' => $batchCode,
                'warehouse_id' => $warehouse->id,
                'batch_type' => CheckinBatchType::Production,
                'product_line_id' => $defaultProductLine?->id,
                'quantity' => 0,
                'production_note' => $productionNote,
                'expected_date' => $parsedExpectedDate,
                'status' => BatchStatus::Completed,
                'created_by' => $createdBy?->id,
                'completed_at' => $now,
            ]);

            $productLines = ProductLine::all();
            $warehouses = Warehouse::with('locations')->get();

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $assets = collect();
            $seenSerials = [];

            foreach ($rows as $row) {
                $serialNo = trim((string) ($row[$columnMap['serial_no']] ?? ''));
                if ($serialNo === '') {
                    $skippedCount++;

                    continue;
                }

                if (isset($seenSerials[$serialNo])) {
                    $skippedCount++;

                    continue;
                }
                $seenSerials[$serialNo] = true;

                // Match product line
                $productLineId = $defaultProductLine?->id;
                if (isset($columnMap['product_line']) && ! empty(trim((string) ($row[$columnMap['product_line']] ?? '')))) {
                    $val = trim((string) $row[$columnMap['product_line']]);
                    $matchedPl = $productLines->first(function ($pl) use ($val) {
                        return mb_strtolower($pl->name, 'UTF-8') === mb_strtolower($val, 'UTF-8')
                            || mb_strtolower($pl->code ?? '', 'UTF-8') === mb_strtolower($val, 'UTF-8');
                    });
                    if ($matchedPl) {
                        $productLineId = $matchedPl->id;
                    }
                }

                if (! $productLineId && $defaultProductLine) {
                    $productLineId = $defaultProductLine->id;
                }

                // Match warehouse
                $targetWarehouseId = $warehouse->id;
                if (isset($columnMap['warehouse']) && ! empty(trim((string) ($row[$columnMap['warehouse']] ?? '')))) {
                    $val = trim((string) $row[$columnMap['warehouse']]);
                    $matchedWh = $warehouses->first(function ($wh) use ($val) {
                        return mb_strtolower($wh->name, 'UTF-8') === mb_strtolower($val, 'UTF-8')
                            || mb_strtolower($wh->code ?? '', 'UTF-8') === mb_strtolower($val, 'UTF-8');
                    });
                    if ($matchedWh) {
                        $targetWarehouseId = $matchedWh->id;
                    }
                }

                // Match location in warehouse
                $locationId = $defaultLocationId;
                if ($targetWarehouseId) {
                    $currentWh = $warehouses->firstWhere('id', $targetWarehouseId);
                    if ($currentWh && isset($columnMap['location']) && ! empty(trim((string) ($row[$columnMap['location']] ?? '')))) {
                        $locVal = trim((string) $row[$columnMap['location']]);
                        $matchedLoc = $currentWh->locations->first(function ($loc) use ($locVal) {
                            return mb_strtolower($loc->name, 'UTF-8') === mb_strtolower($locVal, 'UTF-8')
                                || mb_strtolower($loc->code ?? '', 'UTF-8') === mb_strtolower($locVal, 'UTF-8');
                        });
                        if ($matchedLoc) {
                            $locationId = $matchedLoc->id;
                        }
                    }
                }

                // Match size
                $size = isset($columnMap['size']) ? trim((string) ($row[$columnMap['size']] ?? '')) : '';
                if ($size === '' && $productLineId) {
                    $pl = $productLines->firstWhere('id', $productLineId);
                    $size = ($pl && $pl->module_width_mm && $pl->module_height_mm)
                        ? "{$pl->module_width_mm}×{$pl->module_height_mm} mm"
                        : '500×500 mm';
                }

                $mfgDate = isset($columnMap['manufactured_date'])
                    ? $this->parseDate($row[$columnMap['manufactured_date']] ?? null)
                    : (isset($columnMap['expected_date']) ? $this->parseDate($row[$columnMap['expected_date']] ?? null) : ($parsedExpectedDate ?: $now->toDateString()));

                $cost = isset($columnMap['purchase_cost'])
                    ? $this->parseNumber($row[$columnMap['purchase_cost']] ?? null)
                    : 0.0;

                $rowNote = isset($columnMap['note']) ? trim((string) ($row[$columnMap['note']] ?? '')) : null;
                $assetNote = $rowNote ?: ($productionNote ? "Nhập từ đợt sản xuất [{$batch->code}]: {$productionNote}" : "Nhập từ đợt sản xuất [{$batch->code}]");

                $existingAsset = Asset::where('serial_no', $serialNo)->first();
                $qrCode = $existingAsset?->qr_code ?: "LED-{$serialNo}";

                if ($existingAsset) {
                    if (! $updateExisting) {
                        $skippedCount++;

                        continue;
                    }

                    $fromStatus = $existingAsset->current_status;
                    $fromWarehouseId = $existingAsset->current_warehouse_id;

                    $existingAsset->update(array_filter([
                        'product_line_id' => $productLineId ?: $existingAsset->product_line_id,
                        'current_warehouse_id' => $targetWarehouseId,
                        'warehouse_location_id' => $locationId ?: $existingAsset->warehouse_location_id,
                        'size' => $size ?: $existingAsset->size,
                        'current_status' => AssetStatus::Ready,
                        'manufactured_date' => $mfgDate ?: $existingAsset->manufactured_date,
                        'purchase_cost' => $cost > 0 ? $cost : $existingAsset->purchase_cost,
                        'qr_code' => $qrCode,
                        'note' => $assetNote ?: $existingAsset->note,
                    ], fn ($v) => $v !== null));

                    $asset = $existingAsset;
                    $updatedCount++;
                } else {
                    $fromStatus = null;
                    $fromWarehouseId = null;

                    $asset = Asset::create([
                        'serial_no' => $serialNo,
                        'qr_code' => $qrCode,
                        'product_line_id' => $productLineId,
                        'size' => $size,
                        'manufactured_date' => $mfgDate,
                        'purchase_date' => $mfgDate,
                        'purchase_cost' => $cost,
                        'accumulated_depreciation' => 0,
                        'useful_life_months' => 36,
                        'depreciation_method' => 'straight_line',
                        'salvage_value' => 0,
                        'current_status' => AssetStatus::Ready,
                        'current_warehouse_id' => $targetWarehouseId,
                        'warehouse_location_id' => $locationId,
                        'note' => $assetNote,
                    ]);

                    $createdCount++;
                }

                $assets->push($asset);

                CheckinBatchItem::firstOrCreate([
                    'checkin_batch_id' => $batch->id,
                    'asset_id' => $asset->id,
                ], [
                    'condition' => 'ok',
                    'is_received' => true,
                    'received_by' => $createdBy?->id,
                    'received_at' => $now,
                ]);

                AssetStatusLog::create([
                    'asset_id' => $asset->id,
                    'from_status' => $fromStatus,
                    'to_status' => AssetStatus::Ready,
                    'from_warehouse_id' => $fromWarehouseId,
                    'to_warehouse_id' => $targetWarehouseId,
                    'source_type' => CheckinBatch::class,
                    'source_id' => $batch->id,
                    'changed_by' => $createdBy?->id,
                    'note' => "Nhập kho từ đợt sản xuất [{$batch->code}]",
                    'created_at' => $now,
                ]);
            }

            if ($assets->isEmpty()) {
                throw new \InvalidArgumentException('Không có thiết bị hợp lệ nào để nhập.');
            }

            $uniquePls = $assets->pluck('product_line_id')->filter()->unique();
            if ($uniquePls->count() === 1) {
                $batch->product_line_id = $uniquePls->first();
            }

            $batch->quantity = $assets->count();
            $batch->save();

            return [
                'batch' => $batch,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'assets' => $assets,
            ];
        });
    }

    public function parseDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) $value);
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y', 'Y/m/d'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Try next format
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function parseNumber(mixed $value): float
    {
        if (! $value) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = preg_replace('/[^\d\.\,]/u', '', trim((string) $value));
        if (preg_match('/^\d{1,3}([\.,]\d{3})+$/', $cleaned)) {
            $cleaned = preg_replace('/[\.,]/', '', $cleaned);
        } else {
            $cleaned = str_replace(',', '.', $cleaned);
        }

        return (float) $cleaned;
    }
}
