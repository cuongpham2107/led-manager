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
            'excel_file' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
            'serials_text' => 'nullable|string',
        ]);

        if (! $request->hasFile('excel_file') && ! $request->filled('serials_text')) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn file Excel hoặc dán danh sách số serial.',
            ], 422);
        }

        try {
            $parsedItems = [];

            if ($request->hasFile('excel_file')) {
                $import = new CheckinBatchImport;
                Excel::import($import, $request->file('excel_file'));

                $rows = $import->rows;

                if (empty($rows)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File Excel không có dữ liệu.',
                    ]);
                }

                $headerRow = $rows[0] ?? [];
                $columnMap = $this->mapColumns($headerRow);
                $startIndex = 1;

                if (! isset($columnMap['serial_no'])) {
                    // Nếu file 1 cột hoặc dòng đầu là số serial trực tiếp
                    $firstCell = trim((string) (is_array($headerRow) ? array_values($headerRow)[0] ?? '' : ''));
                    if ($firstCell !== '') {
                        $slug = Str::slug($firstCell, '_');
                        $columnMap['serial_no'] = 0;
                        if (in_array($slug, ['so_seri', 'seri', 'serial', 'serial_no', 'so_serial', 'serial_number', 'ma_tai_san', 'ma_so_seri', 'ma_thiet_bi', 'ma_serial', 'stt'])) {
                            $startIndex = 1;
                        } else {
                            $startIndex = 0; // Dòng 1 chính là dữ liệu serial
                        }
                    }
                }

                if (! isset($columnMap['serial_no'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy cột Số serial trong file Excel.',
                    ]);
                }

                foreach ($rows as $index => $row) {
                    if ($index < $startIndex) {
                        continue;
                    }

                    $values = is_array($row) ? array_values($row) : [];
                    $serialNo = trim((string) ($values[$columnMap['serial_no']] ?? ''));

                    if ($serialNo === '') {
                        continue;
                    }

                    $productLineVal = isset($columnMap['product_line']) ? trim((string) ($values[$columnMap['product_line']] ?? '')) : null;
                    $sizeVal = isset($columnMap['size']) ? trim((string) ($values[$columnMap['size']] ?? '')) : null;
                    $noteVal = isset($columnMap['note']) ? trim((string) ($values[$columnMap['note']] ?? '')) : null;

                    $parsedItems[] = [
                        'serial_no' => $serialNo,
                        'product_line' => $productLineVal,
                        'size' => $sizeVal,
                        'note' => $noteVal,
                    ];
                }
            } elseif ($request->filled('serials_text')) {
                $lines = preg_split('/[\r\n,;\t]+/', (string) $request->input('serials_text')) ?: [];
                foreach ($lines as $line) {
                    $serialNo = trim((string) $line);
                    if ($serialNo === '') {
                        continue;
                    }

                    $slug = Str::slug($serialNo, '_');
                    if (in_array($slug, ['so_seri', 'seri', 'serial', 'serial_no', 'so_serial', 'serial_number', 'ma_tai_san', 'ma_so_seri', 'ma_thiet_bi', 'stt'])) {
                        continue;
                    }

                    $parsedItems[] = [
                        'serial_no' => $serialNo,
                        'product_line' => null,
                        'size' => null,
                        'note' => null,
                    ];
                }
            }

            if (empty($parsedItems)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy số serial hợp lệ nào để tích chọn.',
                ]);
            }

            $assets = [];
            $seenSerials = [];
            $createdCount = 0;
            $matchedCount = 0;

            foreach ($parsedItems as $item) {
                $serialNo = $item['serial_no'];
                $normalizedSerial = strtolower($serialNo);

                if (isset($seenSerials[$normalizedSerial])) {
                    continue;
                }
                $seenSerials[$normalizedSerial] = true;

                $asset = Asset::with('productLine')
                    ->where('serial_no', $serialNo)
                    ->orWhereRaw('LOWER(TRIM(serial_no)) = ?', [$normalizedSerial])
                    ->first();

                if (! $asset) {
                    $productLine = null;
                    if (! empty($item['product_line'])) {
                        $plVal = $item['product_line'];
                        $productLine = ProductLine::where('name', $plVal)
                            ->orWhere('code', $plVal)
                            ->first();
                    }
                    if (! $productLine) {
                        $productLine = ProductLine::where('is_active', true)->first();
                    }

                    $size = ! empty($item['size']) ? $item['size'] : '500 x 500 mm';

                    $asset = Asset::create([
                        'serial_no' => $serialNo,
                        'product_line_id' => $productLine?->id,
                        'size' => $size,
                        'current_status' => AssetStatus::NewlyAdded,
                        'note' => $item['note'] ?? null,
                    ]);
                    $asset->load('productLine');
                    $createdCount++;
                } else {
                    $matchedCount++;
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
                    'size' => (string) ($asset->size ?? '500 x 500 mm'),
                    'status' => (string) $statusLabel,
                    'status_raw' => (string) ($asset->current_status instanceof AssetStatus ? $asset->current_status->value : $asset->current_status),
                    'status_color' => (string) $statusColor,
                ];
            }

            $message = 'Đã tích chọn '.count($assets).' thiết bị';
            if ($createdCount > 0) {
                $message .= " ({$createdCount} thiết bị mới được tạo)";
            }
            $message .= '.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'assets' => $assets,
                'created_count' => $createdCount,
                'matched_count' => $matchedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý dữ liệu: '.$e->getMessage(),
            ]);
        }
    }

    protected function mapColumns(array $firstRow): array
    {
        $columnMap = [];
        $headers = is_array($firstRow) ? array_values($firstRow) : [];

        foreach ($headers as $index => $header) {
            $slug = Str::slug(trim((string) $header), '_');
            if (in_array($slug, ['so_seri', 'seri', 'serial', 'serial_no', 'so_serial', 'serial_number', 'ma_tai_san', 'ma_so_seri', 'ma_thiet_bi', 'ma_serial', 'ma_seri'])) {
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
