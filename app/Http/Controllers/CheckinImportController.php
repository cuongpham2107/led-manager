<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Imports\CheckinBatchImport;
use App\Models\Asset;
use App\Models\ProductLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CheckinImportController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new CheckinBatchImport;
            Excel::import($import, $request->file('excel_file'));

            $rows = $import->rows;

            if (empty($rows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File không có dữ liệu.',
                ]);
            }

            $headerRow = $rows[0] ?? [];
            $columnMap = $this->mapColumns($headerRow);

            if (! isset($columnMap['serial_no'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy cột "Số Seri" trong file. Header: '.implode(', ', array_values($headerRow)),
                ]);
            }

            $assets = [];
            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                $values = is_array($row) ? array_values($row) : [];
                $serialNo = trim((string) ($values[$columnMap['serial_no']] ?? ''));

                if ($serialNo === '') {
                    continue;
                }

                $asset = Asset::with('productLine')->where('serial_no', $serialNo)->first();

                if (! $asset) {
                    $productLine = null;
                    if (isset($columnMap['product_line']) && ! empty($values[$columnMap['product_line']])) {
                        $plVal = trim((string) $values[$columnMap['product_line']]);
                        $productLine = ProductLine::where('name', $plVal)
                            ->orWhere('code', $plVal)
                            ->first();
                    }
                    if (! $productLine) {
                        $productLine = ProductLine::where('is_active', true)->first();
                    }

                    $size = isset($columnMap['size']) && ! empty($values[$columnMap['size']])
                        ? trim((string) $values[$columnMap['size']])
                        : '500×500 mm';

                    $asset = Asset::create([
                        'serial_no' => $serialNo,
                        'product_line_id' => $productLine?->id,
                        'size' => $size,
                        'current_status' => AssetStatus::NewlyAdded,
                        'note' => isset($columnMap['note']) ? trim((string) ($values[$columnMap['note']] ?? '')) : null,
                    ]);
                    $asset->load('productLine');
                }

                $statusLabel = $asset->current_status instanceof AssetStatus
                    ? $asset->current_status->getLabel()
                    : 'Mới';
                $statusColor = $asset->current_status instanceof AssetStatus
                    ? $asset->current_status->getColor()
                    : 'primary';

                $assets[] = [
                    'id' => (int) $asset->id,
                    'serial_no' => (string) $asset->serial_no,
                    'product_line_id' => (int) $asset->product_line_id,
                    'name' => (string) ($asset->productLine?->name ?? 'LED'),
                    'size' => (string) ($asset->size ?? '0.5×0.5 m'),
                    'status' => (string) $statusLabel,
                    'status_raw' => (string) ($asset->current_status instanceof AssetStatus ? $asset->current_status->value : $asset->current_status),
                    'status_color' => (string) $statusColor,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã import '.count($assets).' thiết bị.',
                'assets' => $assets,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đọc file: '.$e->getMessage(),
            ]);
        }
    }

    protected function mapColumns(array $firstRow): array
    {
        $columnMap = [];
        $headers = is_array($firstRow) ? array_values($firstRow) : [];

        foreach ($headers as $index => $header) {
            $slug = Str::slug(trim((string) $header), '_');
            if (in_array($slug, ['so_seri', 'seri', 'serial', 'serial_no', 'ma_tai_san', 'ma_so_seri', 'ma_thiet_bi'])) {
                $columnMap['serial_no'] = $index;
            } elseif (in_array($slug, ['dong_san_pham', 'product_line', 'model', 'loai_led', 'dong_led', 'san_pham'])) {
                $columnMap['product_line'] = $index;
            } elseif (in_array($slug, ['kich_thuoc', 'size', 'quy_cach'])) {
                $columnMap['size'] = $index;
            } elseif (in_array($slug, ['ghi_chu', 'note', 'mo_ta'])) {
                $columnMap['note'] = $index;
            }
        }

        return $columnMap;
    }
}
