<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\AssetResource;
use App\Models\Asset;
use App\Models\ProductLine;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Carbon\Carbon;
use DateTimeInterface;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Qalainau\UniverSheet\SpreadsheetField;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    public function getTitle(): string
    {
        return 'Danh sách tài sản LED';
    }

    public function getSubheading(): ?string
    {
        $user = auth()->user();
        if ($whId = $user?->getScopedWarehouseId()) {
            $count = Asset::where('current_warehouse_id', $whId)->count();
            $whName = $user->warehouse?->name ?? 'Kho công tác';

            return "{$count} thiết bị thuộc {$whName}";
        }

        $count = Asset::count();

        return "{$count} / {$count} thiết bị toàn hệ thống";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importExcel')
                ->label('Nhập Excel (Bảng tính)')
                ->icon('heroicon-o-table-cells')
                ->color('primary')
                ->modalHeading('Bảng tính nhập dữ liệu thiết bị LED (Univer Sheet)')
                ->modalDescription('Nhập trực tiếp vào các ô hoặc copy/paste hàng loạt từ Excel / Google Sheets (Ctrl+C & Ctrl+V). Không cần tải file lên.')
                ->modalSubmitActionLabel('Lưu & Nhập vào hệ thống')
                ->modalWidth(Width::SevenExtraLarge)
                ->form([
                    Grid::make(3)
                        ->schema([
                            Select::make('default_product_line_id')
                                ->label('Dòng sản phẩm (Gợi ý / Mặc định)')
                                ->options(fn () => ProductLine::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->placeholder('Chọn dòng sản phẩm...')
                                ->helperText('Áp dụng tự động nếu ô "Dòng sản phẩm" để trống'),

                            Select::make('default_warehouse_id')
                                ->label('Kho lưu trữ (Gợi ý / Mặc định)')
                                ->options(fn () => Warehouse::where('is_active', true)->pluck('name', 'id'))
                                ->default(fn () => auth()->user()?->getScopedWarehouseId())
                                ->disabled(fn () => (bool) auth()->user()?->getScopedWarehouseId())
                                ->dehydrated()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('default_warehouse_location_id', null))
                                ->placeholder('Chọn kho lưu trữ...')
                                ->helperText('Áp dụng tự động nếu ô "Kho lưu trữ" để trống'),

                            Select::make('default_warehouse_location_id')
                                ->label('Vị trí kho (Gợi ý / Mặc định)')
                                ->options(function (callable $get) {
                                    $whId = $get('default_warehouse_id') ?: auth()->user()?->getScopedWarehouseId();
                                    if (! $whId) {
                                        return [];
                                    }

                                    return WarehouseLocation::where('warehouse_id', $whId)->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->placeholder('Chọn vị trí...')
                                ->helperText('Áp dụng tự động nếu ô "Vị trí" để trống'),
                        ]),

                    Toggle::make('update_existing')
                        ->label('Cập nhật nếu số Seri đã tồn tại trong hệ thống')
                        ->default(true)
                        ->helperText('Nếu bật, thiết bị trùng số Seri sẽ được cập nhật thông tin mới. Nếu tắt, dòng đó sẽ được bỏ qua.'),

                    SpreadsheetField::make('sheet_data')
                        ->label('Bảng tính nhập liệu')
                        ->default(fn () => $this->getDefaultSheetData())
                        ->height('55vh')
                        ->minHeight('400px')
                        ->showToolbar(true)
                        ->showFormulaBar(true)
                        ->showSheetTabs(true)
                        ->showContextMenu(true),
                ])
                ->action(function (array $data): void {
                    $this->importFromUniverSheet($data);
                }),

            Action::make('exportExcel')
                ->label('Xuất Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->exportExcel()),

            CreateAction::make()
                ->label('+ Thêm tài sản')
                ->modalHeading('Thêm tài sản LED mới')
                ->modalWidth(Width::FourExtraLarge),
        ];
    }

    public function getDefaultSheetData(): array
    {
        $headers = [
            'Số Seri',
            'Dòng sản phẩm',
            'Kho lưu trữ',
            'Vị trí',
            'Kích thước',
            'Ngày sản xuất',
            'Ngày mua',
            'Nguyên giá',
            'Ghi chú',
        ];

        $sampleRow = [
            'P26-HN-SAMPLE01',
            'P2.6 Sự kiện',
            'Kho Hà Nội',
            'HN-K1',
            '500×500 mm',
            '01/01/2026',
            '15/01/2026',
            3500000,
            'Hàng mẫu đợt 1',
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

        return [
            'id' => 'workbook-led',
            'sheetOrder' => ['sheet-led-01'],
            'name' => 'Tài sản LED',
            'appVersion' => '3.0.0-alpha',
            'sheets' => [
                'sheet-led-01' => [
                    'id' => 'sheet-led-01',
                    'name' => 'Tài sản LED',
                    'rowCount' => 100,
                    'columnCount' => 10,
                    'cellData' => $cellData,
                    'columnData' => [
                        0 => ['w' => 160],
                        1 => ['w' => 160],
                        2 => ['w' => 140],
                        3 => ['w' => 120],
                        4 => ['w' => 120],
                        5 => ['w' => 120],
                        6 => ['w' => 120],
                        7 => ['w' => 130],
                        8 => ['w' => 220],
                    ],
                ],
            ],
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        $user = auth()->user();
        $warehouseId = $user?->getScopedWarehouseId();

        $filename = 'danh-sach-tai-san-led-'.($warehouseId ? 'kho-'.$warehouseId.'-' : '').now()->format('Ymd-His').'.xlsx';
        $tempPath = tempnam(sys_get_temp_dir(), 'export_asset_');

        $writer = new Writer;
        $writer->openToFile($tempPath);

        $headerStyle = (new Style)->setFontBold();
        $writer->addRow(Row::fromValues([
            'Số Seri',
            'Mã QR',
            'Dòng sản phẩm',
            'Kho hiện tại',
            'Vị trí trong kho',
            'Kích thước',
            'Trạng thái',
            'Ngày sản xuất',
            'Ngày mua',
            'Nguyên giá (VNĐ)',
            'Khấu hao lũy kế (VNĐ)',
            'Giá trị còn lại (VNĐ)',
            'Thời gian sử dụng (tháng)',
            'Ghi chú',
        ], $headerStyle));

        $query = Asset::with(['productLine', 'currentWarehouse', 'warehouseLocation']);
        if ($warehouseId) {
            $query->where('current_warehouse_id', $warehouseId);
        }

        $query->chunk(200, function ($assets) use ($writer) {
            foreach ($assets as $asset) {
                $statusLabel = $asset->current_status instanceof AssetStatus
                    ? $asset->current_status->getLabel()
                    : (string) $asset->current_status;

                $writer->addRow(Row::fromValues([
                    $asset->serial_no,
                    $asset->qr_code ?? '',
                    $asset->productLine?->name ?? '',
                    $asset->currentWarehouse?->name ?? 'Chưa gán kho',
                    $asset->warehouseLocation?->name ?? '',
                    $asset->size ?? '',
                    $statusLabel,
                    $asset->manufactured_date ? $asset->manufactured_date->format('d/m/Y') : '',
                    $asset->purchase_date ? $asset->purchase_date->format('d/m/Y') : '',
                    (float) ($asset->purchase_cost ?? 0),
                    (float) ($asset->accumulated_depreciation ?? 0),
                    (float) $asset->current_book_value,
                    (int) ($asset->useful_life_months ?? 36),
                    $asset->note ?? '',
                ]));
            }
        });

        $writer->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    protected function importFromUniverSheet(array $data): void
    {
        $sheetData = $data['sheet_data'] ?? [];
        $rows = [];
        $headers = [];

        if (is_array($sheetData) && isset($sheetData['sheets'])) {
            $firstSheet = collect($sheetData['sheets'])->first();
            if (isset($firstSheet['cellData']) && is_array($firstSheet['cellData'])) {
                $rawCells = $firstSheet['cellData'];
                ksort($rawCells, SORT_NUMERIC);

                // Row 0 is headers
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

        if (empty($rows)) {
            Notification::make()
                ->title('Chưa có dữ liệu để nhập')
                ->body('Vui lòng nhập hoặc dán ít nhất 1 dòng dữ liệu vào bảng tính.')
                ->warning()
                ->send();

            return;
        }

        // Map column indices
        $columnMap = [];
        foreach ($headers as $index => $header) {
            $slug = Str::slug(trim((string) $header), '_');
            if (in_array($slug, ['so_seri', 'seri', 'serial', 'serial_no', 'ma_tai_san', 'ma_so_seri'])) {
                $columnMap['serial_no'] = $index;
            } elseif (in_array($slug, ['dong_san_pham', 'product_line', 'model', 'loai_led', 'dong_led'])) {
                $columnMap['product_line'] = $index;
            } elseif (in_array($slug, ['kho', 'kho_hang', 'kho_luu_tru', 'kho_hien_tai', 'warehouse'])) {
                $columnMap['warehouse'] = $index;
            } elseif (in_array($slug, ['vi_tri', 'vi_tri_kho', 'ke', 'location', 'khu_vuc'])) {
                $columnMap['location'] = $index;
            } elseif (in_array($slug, ['kich_thuoc', 'size', 'quy_cach'])) {
                $columnMap['size'] = $index;
            } elseif (in_array($slug, ['trang_thai', 'status', 'tinh_trang'])) {
                $columnMap['status'] = $index;
            } elseif (in_array($slug, ['ngay_san_xuat', 'manufactured_date', 'nsx'])) {
                $columnMap['manufactured_date'] = $index;
            } elseif (in_array($slug, ['ngay_mua', 'purchase_date'])) {
                $columnMap['purchase_date'] = $index;
            } elseif (in_array($slug, ['nguyen_gia', 'gia_mua', 'purchase_cost', 'gia_tri'])) {
                $columnMap['purchase_cost'] = $index;
            } elseif (in_array($slug, ['ghi_chu', 'note', 'mo_ta'])) {
                $columnMap['note'] = $index;
            } elseif (in_array($slug, ['ma_qr', 'qr_code', 'qr'])) {
                $columnMap['qr_code'] = $index;
            }
        }

        if (! isset($columnMap['serial_no'])) {
            Notification::make()
                ->title('Không tìm thấy cột Số Seri')
                ->body('Hàng tiêu đề đầu tiên cần có cột "Số Seri" hoặc "serial_no".')
                ->danger()
                ->send();

            return;
        }

        $productLines = ProductLine::all();
        $warehouses = Warehouse::with('locations')->get();
        $userWarehouseId = auth()->user()?->getScopedWarehouseId();
        $defaultWarehouseId = $userWarehouseId ?: ($data['default_warehouse_id'] ?? null);
        $defaultProductLineId = $data['default_product_line_id'] ?? null;
        $defaultLocationId = $data['default_warehouse_location_id'] ?? null;
        $updateExisting = (bool) ($data['update_existing'] ?? true);

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $serialNo = trim((string) ($row[$columnMap['serial_no']] ?? ''));
                if ($serialNo === '' || $serialNo === 'P26-HN-SAMPLE01' || $serialNo === 'P39-HN-SAMPLE02') {
                    $skippedCount++;

                    continue;
                }

                // Match product line
                $productLineId = $defaultProductLineId;
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

                if (! $productLineId && $productLines->isNotEmpty()) {
                    $productLineId = $productLines->first()->id;
                }

                // Match warehouse
                $warehouseId = $defaultWarehouseId;
                if (! $userWarehouseId && isset($columnMap['warehouse']) && ! empty(trim((string) ($row[$columnMap['warehouse']] ?? '')))) {
                    $val = trim((string) $row[$columnMap['warehouse']]);
                    $matchedWh = $warehouses->first(function ($wh) use ($val) {
                        return mb_strtolower($wh->name, 'UTF-8') === mb_strtolower($val, 'UTF-8')
                            || mb_strtolower($wh->code ?? '', 'UTF-8') === mb_strtolower($val, 'UTF-8');
                    });
                    if ($matchedWh) {
                        $warehouseId = $matchedWh->id;
                    }
                }

                // Match location in warehouse
                $locationId = null;
                if ($warehouseId) {
                    $currentWh = $warehouses->firstWhere('id', $warehouseId);
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

                    if (! $locationId && $defaultLocationId) {
                        $locationId = $defaultLocationId;
                    }
                }

                $size = isset($columnMap['size']) ? trim((string) ($row[$columnMap['size']] ?? '')) : '';
                if ($size === '') {
                    $pl = $productLines->firstWhere('id', $productLineId);
                    $size = ($pl && $pl->module_width_mm && $pl->module_height_mm)
                        ? "{$pl->module_width_mm}×{$pl->module_height_mm} mm"
                        : '500×500 mm';
                }

                $status = isset($columnMap['status'])
                    ? $this->parseStatus($row[$columnMap['status']] ?? null)
                    : ($existingAsset?->current_status ?? AssetStatus::Ready);

                $mfgDate = isset($columnMap['manufactured_date'])
                    ? $this->parseDate($row[$columnMap['manufactured_date']] ?? null)
                    : null;

                $purDate = isset($columnMap['purchase_date'])
                    ? $this->parseDate($row[$columnMap['purchase_date']] ?? null)
                    : now()->toDateString();

                $cost = isset($columnMap['purchase_cost'])
                    ? $this->parseNumber($row[$columnMap['purchase_cost']] ?? null)
                    : 0.0;

                $note = isset($columnMap['note']) ? trim((string) ($row[$columnMap['note']] ?? '')) : null;

                $existingAsset = Asset::where('serial_no', $serialNo)->first();

                // QR Code is strictly auto-generated: "LED-{$serialNo}"
                $qrCode = $existingAsset?->qr_code ?: "LED-{$serialNo}";

                if ($existingAsset) {
                    if ($updateExisting) {
                        $existingAsset->update(array_filter([
                            'product_line_id' => $productLineId,
                            'current_warehouse_id' => $warehouseId,
                            'warehouse_location_id' => $locationId ?: $existingAsset->warehouse_location_id,
                            'size' => $size,
                            'current_status' => $status,
                            'manufactured_date' => $mfgDate ?: $existingAsset->manufactured_date,
                            'purchase_date' => $purDate ?: $existingAsset->purchase_date,
                            'purchase_cost' => $cost > 0 ? $cost : $existingAsset->purchase_cost,
                            'qr_code' => $qrCode,
                            'note' => $note ?: $existingAsset->note,
                        ], fn ($v) => $v !== null));
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    Asset::create([
                        'serial_no' => $serialNo,
                        'qr_code' => $qrCode,
                        'product_line_id' => $productLineId,
                        'size' => $size,
                        'manufactured_date' => $mfgDate,
                        'purchase_date' => $purDate,
                        'purchase_cost' => $cost,
                        'accumulated_depreciation' => 0,
                        'useful_life_months' => 36,
                        'depreciation_method' => 'straight_line',
                        'salvage_value' => 0,
                        'current_status' => $status,
                        'current_warehouse_id' => $warehouseId,
                        'warehouse_location_id' => $locationId,
                        'note' => $note,
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Notification::make()
                ->title('Có lỗi xảy ra khi lưu dữ liệu')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Nhập dữ liệu thành công')
            ->body("Đã xử lý: Thêm mới {$createdCount} thiết bị, cập nhật {$updatedCount} thiết bị".($skippedCount > 0 ? ", bỏ qua {$skippedCount} dòng." : '.'))
            ->success()
            ->send();
    }

    protected function parseDate(mixed $value): ?string
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

    protected function parseNumber(mixed $value): float
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

    protected function parseStatus(mixed $value): AssetStatus
    {
        if (! $value) {
            return AssetStatus::Ready;
        }

        $val = mb_strtolower(trim((string) $value), 'UTF-8');

        if (str_contains($val, 'sự kiện') || str_contains($val, 'in_event') || str_contains($val, 'su kien')) {
            return AssetStatus::InEvent;
        }

        if (str_contains($val, 'vận chuyển') || str_contains($val, 'in_transit') || str_contains($val, 'van chuyen')) {
            return AssetStatus::InTransit;
        }

        if (str_contains($val, 'sửa') || str_contains($val, 'bảo dưỡng') || str_contains($val, 'repair')) {
            return AssetStatus::Repairing;
        }

        if (str_contains($val, 'mất') || str_contains($val, 'thất lạc') || str_contains($val, 'missing')) {
            return AssetStatus::Missing;
        }

        if (str_contains($val, 'thanh lý') || str_contains($val, 'disposed')) {
            return AssetStatus::Disposed;
        }

        return AssetStatus::Ready;
    }
}
