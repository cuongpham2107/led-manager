<?php

namespace App\Filament\Resources\CheckinBatches\Actions;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\ProductLine;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Qalainau\UniverSheet\SpreadsheetField;

class ImportCheckinBatchItemsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_checkin_batch_items';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Import Excel')
            ->icon('heroicon-o-table-cells')
            ->color('success')
            ->modalHeading(fn ($record) => $record instanceof CheckinBatch && $record->exists
                ? "Import thiết bị từ Excel / Bảng tính — Đợt: {$record->code}"
                : 'Import thiết bị từ Excel / Bảng tính')
            ->modalDescription('Nhập trực tiếp vào các ô hoặc copy/paste hàng loạt từ Excel / Google Sheets (Ctrl+C & Ctrl+V). Không cần upload file.')
            ->modalSubmitActionLabel('Lưu & Thêm vào đợt nhập')
            ->modalWidth(Width::SevenExtraLarge)
            ->form(function ($schema) {
                $livewire = $schema->getLivewire();
                /** @var CheckinBatch|null $record */
                $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;

                return $schema->components([
                    Grid::make(3)
                        ->schema([
                            Select::make('default_product_line_id')
                                ->label('Dòng sản phẩm (Mặc định)')
                                ->options(fn () => ProductLine::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->placeholder('Chọn dòng sản phẩm...')
                                ->helperText('Áp dụng tự động nếu cột "Dòng sản phẩm" để trống'),

                            Select::make('default_warehouse_id')
                                ->label('Kho lưu trữ (Mặc định)')
                                ->options(fn () => Warehouse::where('is_active', true)->pluck('name', 'id'))
                                ->default(fn () => ($record instanceof CheckinBatch && $record->exists)
                                    ? $record->warehouse_id
                                    : Auth::user()?->getScopedWarehouseId())
                                ->disabled(fn () => ($record instanceof CheckinBatch && $record->exists)
                                    || (bool) Auth::user()?->getScopedWarehouseId())
                                ->dehydrated()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('default_warehouse_location_id', null))
                                ->placeholder('Chọn kho lưu trữ...')
                                ->helperText('Áp dụng tự động nếu cột "Kho lưu trữ" để trống'),

                            Select::make('default_warehouse_location_id')
                                ->label('Vị trí kho (Mặc định)')
                                ->options(function (callable $get) use ($record) {
                                    $whId = $get('default_warehouse_id')
                                        ?: (($record instanceof CheckinBatch && $record->exists) ? $record->warehouse_id : null)
                                        ?: Auth::user()?->getScopedWarehouseId();

                                    if (! $whId) {
                                        return [];
                                    }

                                    return WarehouseLocation::where('warehouse_id', $whId)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->placeholder('Chọn vị trí kho...')
                                ->helperText('Áp dụng tự động nếu cột "Vị trí" để trống'),
                        ]),

                    Toggle::make('create_if_not_exists')
                        ->label('Tự động tạo mới thiết bị nếu số Seri chưa có trong hệ thống')
                        ->default(true)
                        ->helperText('Nếu bật, số Seri mới sẽ được tạo trong hệ thống và gán vào đợt nhập. Nếu tắt, chỉ thêm thiết bị đã có.'),

                    SpreadsheetField::make('sheet_data')
                        ->label('Bảng tính nhập liệu')
                        ->default(fn () => static::getDefaultSheetData($record))
                        ->height('55vh')
                        ->minHeight('400px')
                        ->showToolbar(true)
                        ->showFormulaBar(true)
                        ->showSheetTabs(true)
                        ->showContextMenu(true),
                ]);
            })
            ->action(function ($livewire, array $data): void {
                /** @var CheckinBatch|null $record */
                $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
                $isExisting = $record instanceof CheckinBatch && $record->exists;

                $parsed = static::parseSheetData($data['sheet_data'] ?? []);
                $headers = $parsed['headers'];
                $rows = $parsed['rows'];

                if (empty($rows)) {
                    Notification::make()
                        ->title('Chưa có dữ liệu để nhập')
                        ->body('Vui lòng nhập hoặc dán ít nhất 1 dòng dữ liệu vào bảng tính.')
                        ->warning()
                        ->send();

                    return;
                }

                $columnMap = static::mapColumns($headers);
                if (! isset($columnMap['serial_no'])) {
                    Notification::make()
                        ->title('Không tìm thấy cột Số Seri')
                        ->body('Hàng tiêu đề đầu tiên cần có cột "Số Seri" hoặc "serial_no".')
                        ->danger()
                        ->send();

                    return;
                }

                $sampleSerials = ['P26-HN-SAMPLE01', 'P26-HN-PROD01', 'P39-HN-SAMPLE02'];
                $validRows = [];
                foreach ($rows as $row) {
                    $serialNo = trim((string) ($row[$columnMap['serial_no']] ?? ''));
                    if ($serialNo === '' || in_array($serialNo, $sampleSerials, true)) {
                        continue;
                    }
                    $validRows[] = $row;
                }

                if (empty($validRows)) {
                    Notification::make()
                        ->title('Không có dữ liệu hợp lệ để nhập')
                        ->body('Vui lòng điền số Seri thực tế (khác dòng mẫu) vào bảng tính.')
                        ->warning()
                        ->send();

                    return;
                }

                $createIfNotExists = (bool) ($data['create_if_not_exists'] ?? true);
                $defaultProductLineId = $data['default_product_line_id'] ?? null;
                $defaultLocationId = $data['default_warehouse_location_id'] ?? null;
                $warehouseId = $isExisting
                    ? $record->warehouse_id
                    : ($data['default_warehouse_id'] ?? Auth::user()?->getScopedWarehouseId());
                $user = Auth::user();

                if (! $isExisting) {
                    static::importIntoCreateForm(
                        $livewire,
                        $validRows,
                        $columnMap,
                        $createIfNotExists,
                        $defaultProductLineId,
                        $defaultLocationId,
                        $warehouseId,
                    );

                    return;
                }

                $addedCount = 0;
                $createdCount = 0;
                $skippedCount = 0;
                $receivedCount = 0;

                DB::transaction(function () use (
                    $record,
                    $validRows,
                    $columnMap,
                    $createIfNotExists,
                    $defaultProductLineId,
                    $defaultLocationId,
                    $user,
                    &$addedCount,
                    &$createdCount,
                    &$skippedCount,
                    &$receivedCount
                ) {
                    foreach ($validRows as $row) {
                        $serialNo = trim((string) ($row[$columnMap['serial_no']] ?? ''));
                        if ($serialNo === '') {
                            continue;
                        }

                        $asset = Asset::where('serial_no', $serialNo)->first();
                        $isNewlyCreated = false;

                        if (! $asset) {
                            if (! $createIfNotExists) {
                                $skippedCount++;

                                continue;
                            }

                            $productLine = null;
                            if (isset($columnMap['product_line']) && ! empty($row[$columnMap['product_line']])) {
                                $plVal = trim((string) $row[$columnMap['product_line']]);
                                $productLine = ProductLine::where('name', $plVal)
                                    ->orWhere('code', $plVal)
                                    ->first();
                            }
                            if (! $productLine && $defaultProductLineId) {
                                $productLine = ProductLine::find($defaultProductLineId);
                            }
                            if (! $productLine) {
                                $productLine = ProductLine::where('is_active', true)->first();
                            }

                            $size = isset($columnMap['size']) && ! empty($row[$columnMap['size']])
                                ? trim((string) $row[$columnMap['size']])
                                : '500×500 mm';

                            $locationId = $defaultLocationId;
                            if (isset($columnMap['location']) && ! empty($row[$columnMap['location']])) {
                                $locVal = trim((string) $row[$columnMap['location']]);
                                $loc = WarehouseLocation::where('warehouse_id', $record->warehouse_id)
                                    ->where(function ($q) use ($locVal) {
                                        $q->where('name', $locVal)->orWhere('code', $locVal);
                                    })->first();
                                if ($loc) {
                                    $locationId = $loc->id;
                                }
                            }

                            $asset = Asset::create([
                                'serial_no' => $serialNo,
                                'product_line_id' => $productLine?->id,
                                'current_warehouse_id' => $record->warehouse_id,
                                'warehouse_location_id' => $locationId,
                                'size' => $size,
                                'current_status' => AssetStatus::NewlyAdded,
                                'note' => isset($columnMap['note']) ? trim((string) ($row[$columnMap['note']] ?? '')) : null,
                            ]);

                            $isNewlyCreated = true;
                            $createdCount++;
                        }

                        $item = CheckinBatchItem::firstOrNew([
                            'checkin_batch_id' => $record->id,
                            'asset_id' => $asset->id,
                        ]);

                        $isNewItem = ! $item->exists;

                        if ($isNewlyCreated) {
                            // Thiết bị mới: thêm vào đợt ở dạng chờ nhận, giữ trạng thái "Mới".
                            // Sẽ chuyển "Sẵn sàng" khi hoàn tất đợt nhập.
                            $item->condition = $item->condition ?: 'ok';
                            $item->is_received = false;
                            $item->received_at = null;
                            $item->received_by = null;
                            $item->save();

                            $asset->update([
                                'current_warehouse_id' => $record->warehouse_id,
                            ]);
                        } else {
                            // Thiết bị đã có: nhận vào kho ngay.
                            $item->condition = 'ok';
                            $item->is_received = true;
                            $item->received_at = now();
                            $item->received_by = $user?->id;
                            $item->save();

                            $oldStatus = $asset->current_status;
                            $asset->update([
                                'current_warehouse_id' => $record->warehouse_id,
                                'current_status' => AssetStatus::Ready,
                            ]);

                            if ($oldStatus !== AssetStatus::Ready) {
                                AssetStatusLog::create([
                                    'asset_id' => $asset->id,
                                    'from_status' => $oldStatus,
                                    'to_status' => AssetStatus::Ready,
                                    'from_warehouse_id' => $asset->current_warehouse_id,
                                    'to_warehouse_id' => $record->warehouse_id,
                                    'source_type' => CheckinBatch::class,
                                    'source_id' => $record->id,
                                    'changed_by' => $user?->id,
                                    'note' => "Import vào đợt nhập: {$record->code}",
                                    'created_at' => now(),
                                ]);
                            }

                            $receivedCount++;
                        }

                        if ($isNewItem) {
                            $addedCount++;
                        }
                    }

                    if ($record->status === BatchStatus::Pending && $receivedCount > 0) {
                        $record->update(['status' => BatchStatus::InProgress]);
                    }
                });

                Notification::make()
                    ->title('Import thành công!')
                    ->body("Đã thêm {$addedCount} thiết bị vào đợt nhập [{$record->code}]".($createdCount > 0 ? " (tạo mới: {$createdCount})" : '').($skippedCount > 0 ? ", bỏ qua {$skippedCount} dòng." : '.'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Import trong chế độ tạo mới (đợt nhập chưa được lưu): tạo/tìm thiết bị rồi
     * đẩy vào danh sách chọn của form qua sự kiện trình duyệt.
     *
     * @param  array<int, array<int, string>>  $validRows
     * @param  array<string, int>  $columnMap
     */
    protected static function importIntoCreateForm(
        $livewire,
        array $validRows,
        array $columnMap,
        bool $createIfNotExists,
        ?int $defaultProductLineId,
        ?int $defaultLocationId,
        ?int $warehouseId,
    ): void {
        $createdCount = 0;
        $skippedCount = 0;
        $assetsPayload = [];

        DB::transaction(function () use (
            $validRows,
            $columnMap,
            $createIfNotExists,
            $defaultProductLineId,
            $defaultLocationId,
            $warehouseId,
            &$createdCount,
            &$skippedCount,
            &$assetsPayload
        ) {
            foreach ($validRows as $row) {
                $serialNo = trim((string) ($row[$columnMap['serial_no']] ?? ''));
                if ($serialNo === '') {
                    continue;
                }

                $asset = Asset::with('productLine')->where('serial_no', $serialNo)->first();

                if (! $asset) {
                    if (! $createIfNotExists) {
                        $skippedCount++;

                        continue;
                    }

                    $productLine = null;
                    if (isset($columnMap['product_line']) && ! empty($row[$columnMap['product_line']])) {
                        $plVal = trim((string) $row[$columnMap['product_line']]);
                        $productLine = ProductLine::where('name', $plVal)
                            ->orWhere('code', $plVal)
                            ->first();
                    }
                    if (! $productLine && $defaultProductLineId) {
                        $productLine = ProductLine::find($defaultProductLineId);
                    }
                    if (! $productLine) {
                        $productLine = ProductLine::where('is_active', true)->first();
                    }

                    $size = isset($columnMap['size']) && ! empty($row[$columnMap['size']])
                        ? trim((string) $row[$columnMap['size']])
                        : '500×500 mm';

                    $locationId = $defaultLocationId;
                    if ($warehouseId && isset($columnMap['location']) && ! empty($row[$columnMap['location']])) {
                        $locVal = trim((string) $row[$columnMap['location']]);
                        $loc = WarehouseLocation::where('warehouse_id', $warehouseId)
                            ->where(function ($q) use ($locVal) {
                                $q->where('name', $locVal)->orWhere('code', $locVal);
                            })->first();
                        if ($loc) {
                            $locationId = $loc->id;
                        }
                    }

                    $asset = Asset::create([
                        'serial_no' => $serialNo,
                        'product_line_id' => $productLine?->id,
                        'current_warehouse_id' => $warehouseId,
                        'warehouse_location_id' => $locationId,
                        'size' => $size,
                        'current_status' => AssetStatus::NewlyAdded,
                        'note' => isset($columnMap['note']) ? trim((string) ($row[$columnMap['note']] ?? '')) : null,
                    ]);
                    $asset->load('productLine');

                    $createdCount++;
                }

                $statusLabel = $asset->current_status instanceof AssetStatus
                    ? $asset->current_status->getLabel()
                    : 'Sẵn sàng trong kho';
                $statusColor = $asset->current_status instanceof AssetStatus
                    ? $asset->current_status->getColor()
                    : 'success';

                $assetsPayload[$asset->id] = [
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
        });

        $assets = array_values($assetsPayload);

        if (empty($assets)) {
            Notification::make()
                ->title('Không có thiết bị nào được thêm')
                ->body('Tất cả số Seri chưa có trong hệ thống và tùy chọn "Tự động tạo mới" đang tắt.')
                ->warning()
                ->send();

            return;
        }

        $livewire->dispatch('checkin-assets-imported', assets: $assets);

        Notification::make()
            ->title('Đã thêm thiết bị vào đợt')
            ->body('Đã thêm '.count($assets).' thiết bị'.($createdCount > 0 ? " (tạo mới: {$createdCount})" : '').($skippedCount > 0 ? ", bỏ qua {$skippedCount} dòng" : '').'. Bấm "Lưu" để tạo đợt nhập.')
            ->success()
            ->send();
    }

    public static function getDefaultSheetData(?CheckinBatch $record = null): array
    {
        $headers = [
            'Số Seri',
            'Dòng sản phẩm',
            'Kích thước',
            'Ghi chú',
        ];

        $sampleRow = [
            'P26-HN-SAMPLE01',
            'P2.6 Sự kiện',
            '500×500 mm',
            'Dán đè hoặc xóa dòng mẫu này để nhập',
        ];

        $cellData = [];
        foreach ($headers as $colIdx => $header) {
            $cellData[0][$colIdx] = [
                'v' => $header,
                's' => [
                    'bl' => 1,
                    'bg' => ['rgb' => '#E5E7EB'],
                    'fs' => 11,
                ],
            ];
        }

        foreach ($sampleRow as $colIdx => $val) {
            $cellData[1][$colIdx] = [
                'v' => $val,
                's' => [
                    'fs' => 11,
                ],
            ];
        }

        $productLines = ProductLine::where('is_active', true)->get();
        $whId = $record?->warehouse_id;
        $locations = $whId ? WarehouseLocation::where('warehouse_id', $whId)->where('is_active', true)->get() : collect();

        $refHeaders = [
            0 => ['v' => 'Dòng sản phẩm (Tên)', 's' => ['bl' => 1, 'bg' => ['rgb' => '#DBEAFE'], 'fs' => 11]],
            1 => ['v' => 'Mã dòng SP', 's' => ['bl' => 1, 'bg' => ['rgb' => '#DBEAFE'], 'fs' => 11]],
            2 => ['v' => 'Vị trí kho', 's' => ['bl' => 1, 'bg' => ['rgb' => '#FEF3C7'], 'fs' => 11]],
            3 => ['v' => 'Mã vị trí', 's' => ['bl' => 1, 'bg' => ['rgb' => '#FEF3C7'], 'fs' => 11]],
        ];

        $refCellData = [0 => $refHeaders];
        $maxRefRows = max($productLines->count(), $locations->count(), 1);

        for ($r = 0; $r < $maxRefRows; $r++) {
            $rowIdx = $r + 1;
            $pl = $productLines->get($r);
            $loc = $locations->get($r);

            if ($pl) {
                $refCellData[$rowIdx][0] = ['v' => $pl->name, 's' => ['fs' => 11]];
                $refCellData[$rowIdx][1] = ['v' => $pl->code ?? '', 's' => ['fs' => 11]];
            }
            if ($loc) {
                $refCellData[$rowIdx][2] = ['v' => $loc->name, 's' => ['fs' => 11]];
                $refCellData[$rowIdx][3] = ['v' => $loc->code ?? '', 's' => ['fs' => 11]];
            }
        }

        return [
            'id' => 'workbook-checkin-import',
            'sheetOrder' => ['sheet-import-01', 'sheet-ref-01'],
            'name' => 'Import Thiết bị',
            'appVersion' => '3.0.0-alpha',
            'sheets' => [
                'sheet-import-01' => [
                    'id' => 'sheet-import-01',
                    'name' => 'Danh sách thiết bị',
                    'rowCount' => 100,
                    'columnCount' => 5,
                    'cellData' => $cellData,
                    'columnData' => [
                        0 => ['w' => 180],
                        1 => ['w' => 180],
                        2 => ['w' => 130],
                        3 => ['w' => 240],
                    ],
                ],
                'sheet-ref-01' => [
                    'id' => 'sheet-ref-01',
                    'name' => 'Danh mục tham khảo',
                    'rowCount' => max(50, $maxRefRows + 10),
                    'columnCount' => 5,
                    'cellData' => $refCellData,
                    'columnData' => [
                        0 => ['w' => 220],
                        1 => ['w' => 130],
                        2 => ['w' => 180],
                        3 => ['w' => 120],
                    ],
                ],
            ],
        ];
    }

    public static function parseSheetData(mixed $sheetData): array
    {
        if (is_string($sheetData)) {
            $trimmed = trim($sheetData);
            $decoded = json_decode($trimmed, true);

            if (is_array($decoded)) {
                $sheetData = $decoded;
            } elseif ($trimmed !== '') {
                // Dữ liệu dán từ Excel / Google Sheets (TSV/CSV)
                return static::parseDelimitedText($trimmed);
            } else {
                $sheetData = null;
            }
        }

        $rows = [];
        $headers = [];

        if (is_array($sheetData) && isset($sheetData['sheets'])) {
            $targetSheet = null;
            foreach ($sheetData['sheets'] as $sheet) {
                if (! empty($sheet['cellData'][0])) {
                    foreach ($sheet['cellData'][0] as $cell) {
                        $val = is_array($cell) ? ($cell['v'] ?? '') : ($cell ?? '');
                        $slug = Str::slug(trim((string) $val), '_');
                        if (in_array($slug, ['so_seri', 'seri', 'serial', 'serial_no', 'ma_tai_san', 'ma_so_seri', 'ma_thiet_bi'])) {
                            $targetSheet = $sheet;
                            break 2;
                        }
                    }
                }
            }

            $firstSheet = $targetSheet ?? (isset($sheetData['sheetOrder'][0]) ? ($sheetData['sheets'][$sheetData['sheetOrder'][0]] ?? null) : null) ?? collect($sheetData['sheets'])->first();
            if (isset($firstSheet['cellData']) && is_array($firstSheet['cellData'])) {
                $rawCells = $firstSheet['cellData'];
                ksort($rawCells, SORT_NUMERIC);

                $headerCells = $rawCells[0] ?? [];
                $maxCol = 0;
                foreach ($rawCells as $r => $rCells) {
                    if (is_array($rCells)) {
                        foreach (array_keys($rCells) as $c) {
                            if ((int) $c > $maxCol) {
                                $maxCol = (int) $c;
                            }
                        }
                    }
                }

                for ($c = 0; $c <= $maxCol; $c++) {
                    $cell = $headerCells[$c] ?? null;
                    $val = is_array($cell) ? ($cell['v'] ?? '') : ($cell ?? '');
                    $headers[$c] = trim((string) $val);
                }

                foreach ($rawCells as $r => $rCells) {
                    if ((int) $r === 0 || ! is_array($rCells)) {
                        continue;
                    }

                    $row = [];
                    $hasValue = false;
                    for ($c = 0; $c <= $maxCol; $c++) {
                        $cell = $rCells[$c] ?? null;
                        $val = is_array($cell) ? ($cell['v'] ?? '') : ($cell ?? '');
                        $strVal = trim((string) $val);
                        if ($strVal !== '') {
                            $hasValue = true;
                        }
                        $row[$c] = $strVal;
                    }

                    if ($hasValue) {
                        $rows[] = $row;
                    }
                }
            }
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Phân tích dữ liệu dán từ Excel / Google Sheets (phân tách bằng Tab hoặc dấu phẩy).
     *
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    protected static function parseDelimitedText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $lines = array_values(array_filter($lines, fn ($line) => trim((string) $line) !== ''));

        if (empty($lines)) {
            return ['headers' => [], 'rows' => []];
        }

        $delimiter = str_contains($lines[0], "\t") ? "\t" : (str_contains($lines[0], ',') ? ',' : "\t");
        $split = fn (string $line): array => array_map(fn ($cell) => trim((string) $cell), explode($delimiter, $line));

        $serialSlugs = ['so_seri', 'seri', 'serial', 'serial_no', 'ma_tai_san', 'ma_so_seri', 'ma_thiet_bi'];
        $firstCells = $split($lines[0]);
        $firstSlug = Str::slug($firstCells[0] ?? '', '_');
        $hasHeaderRow = in_array($firstSlug, $serialSlugs, true);

        if ($hasHeaderRow) {
            $headers = $firstCells;
            array_shift($lines);
        } else {
            $headers = ['Số Seri', 'Dòng sản phẩm', 'Kích thước', 'Ghi chú'];
        }

        $maxCol = count($headers) - 1;
        $rows = [];

        foreach ($lines as $line) {
            $cells = $split($line);
            $maxCol = max($maxCol, count($cells) - 1);

            $row = [];
            $hasValue = false;
            for ($c = 0; $c <= $maxCol; $c++) {
                $val = trim((string) ($cells[$c] ?? ''));
                if ($val !== '') {
                    $hasValue = true;
                }
                $row[$c] = $val;
            }

            if ($hasValue) {
                $rows[] = $row;
            }
        }

        for ($c = 0; $c <= $maxCol; $c++) {
            if (! isset($headers[$c])) {
                $headers[$c] = '';
            }
        }
        ksort($headers);

        return ['headers' => $headers, 'rows' => $rows];
    }

    public static function mapColumns(array $headers): array
    {
        $columnMap = [];
        foreach ($headers as $index => $header) {
            $slug = Str::slug(trim((string) $header), '_');
            if (in_array($slug, ['so_seri', 'seri', 'serial', 'serial_no', 'ma_tai_san', 'ma_so_seri', 'ma_thiet_bi'])) {
                $columnMap['serial_no'] = $index;
            } elseif (in_array($slug, ['dong_san_pham', 'product_line', 'model', 'loai_led', 'dong_led', 'san_pham'])) {
                $columnMap['product_line'] = $index;
            } elseif (in_array($slug, ['vi_tri', 'vi_tri_kho', 'ke', 'location', 'khu_vuc'])) {
                $columnMap['location'] = $index;
            } elseif (in_array($slug, ['kich_thuoc', 'size', 'quy_cach'])) {
                $columnMap['size'] = $index;
            } elseif (in_array($slug, ['ghi_chu', 'note', 'mo_ta'])) {
                $columnMap['note'] = $index;
            }
        }

        return $columnMap;
    }
}
