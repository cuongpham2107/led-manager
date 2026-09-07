<?php

namespace App\Filament\Resources\CheckinBatches\Actions;

use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\ProductionBatchService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Qalainau\UniverSheet\SpreadsheetField;

class CreateProductionBatchAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'create_production_batch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('CreateProductionBatch:CheckinBatch')
            ->label('Tạo đợt nhập từ sản xuất / Thiết bị mới')
            ->icon('heroicon-o-table-cells')
            ->color('success')
            ->modalHeading('Bảng tính nhập dữ liệu thiết bị sản xuất (Univer Sheet)')
            ->modalDescription('Nhập trực tiếp vào các ô hoặc copy/paste hàng loạt từ Excel / Google Sheets (Ctrl+C & Ctrl+V). Không cần tải file lên.')
            ->modalSubmitActionLabel('Lưu & Tạo đợt nhập kho')
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
                            ->default(function () {
                                $user = Auth::user();

                                return ($user instanceof User ? $user->getScopedWarehouseId() : null) ?? Warehouse::where('is_active', true)->first()?->id;
                            })
                            ->disabled(function () {
                                $user = Auth::user();

                                return (bool) ($user instanceof User ? $user->getScopedWarehouseId() : null);
                            })
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('default_warehouse_location_id', null))
                            ->placeholder('Chọn kho lưu trữ...')
                            ->helperText('Áp dụng tự động nếu ô "Kho lưu trữ" để trống')
                            ->required(),

                        Select::make('default_warehouse_location_id')
                            ->label('Vị trí kho (Gợi ý / Mặc định)')
                            ->options(function (callable $get) {
                                $user = Auth::user();
                                $scopedId = $user instanceof User ? $user->getScopedWarehouseId() : null;
                                $whId = $get('default_warehouse_id') ?: $scopedId;
                                if (! $whId) {
                                    return [];
                                }

                                return WarehouseLocation::where('warehouse_id', $whId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn vị trí...')
                            ->helperText('Áp dụng tự động nếu ô "Vị trí" để trống'),
                    ]),

                Grid::make(2)
                    ->schema([
                        DatePicker::make('expected_date')
                            ->label('Ngày dự kiến')
                            ->default(now()->toDateString())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->placeholder('DD/MM/YYYY')
                            ->helperText('Ngày dự kiến hoàn thành sản xuất / nhập kho'),

                        TextInput::make('production_note')
                            ->label('Ghi chú đợt sản xuất')
                            ->placeholder('VD: Lô sản xuất ngày 07/09, kiểm tra QC đạt chuẩn...')
                            ->maxLength(255),
                    ]),

                Toggle::make('update_existing')
                    ->label('Cập nhật nếu số Seri đã tồn tại trong hệ thống')
                    ->default(true)
                    ->helperText('Nếu bật, thiết bị trùng số Seri sẽ được cập nhật thông tin và đưa vào đợt nhập. Nếu tắt, dòng đó sẽ được bỏ qua.'),

                SpreadsheetField::make('sheet_data')
                    ->label('Bảng tính nhập liệu')
                    ->default(fn () => static::getDefaultSheetData())
                    ->height('55vh')
                    ->minHeight('400px')
                    ->showToolbar(true)
                    ->showFormulaBar(true)
                    ->showSheetTabs(true)
                    ->showContextMenu(true),
            ])
            ->action(function (array $data): void {
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

                $user = Auth::user();
                $scopedId = $user instanceof User ? $user->getScopedWarehouseId() : null;
                $warehouseId = $scopedId ?: ($data['default_warehouse_id'] ?? null);
                $warehouse = Warehouse::find($warehouseId) ?? Warehouse::where('is_active', true)->first();

                if (! $warehouse) {
                    Notification::make()
                        ->title('Chưa chọn kho lưu trữ')
                        ->body('Vui lòng chọn kho lưu trữ mặc định.')
                        ->danger()
                        ->send();

                    return;
                }

                $defaultProductLine = ! empty($data['default_product_line_id']) ? ProductLine::find($data['default_product_line_id']) : null;
                $defaultLocationId = ! empty($data['default_warehouse_location_id']) ? (int) $data['default_warehouse_location_id'] : null;
                $updateExisting = (bool) ($data['update_existing'] ?? true);
                $productionNote = $data['production_note'] ?? null;
                $expectedDate = $data['expected_date'] ?? null;

                try {
                    $service = app(ProductionBatchService::class);
                    $result = $service->createFromSpreadsheet(
                        rows: $validRows,
                        columnMap: $columnMap,
                        warehouse: $warehouse,
                        defaultProductLine: $defaultProductLine,
                        defaultLocation: $defaultLocationId,
                        productionNote: $productionNote,
                        updateExisting: $updateExisting,
                        createdBy: Auth::user(),
                        expectedDate: $expectedDate,
                    );
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Có lỗi khi tạo đợt nhập kho')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $batch = $result['batch'];
                $count = $result['assets']->count();
                $createdCount = $result['created_count'];
                $updatedCount = $result['updated_count'];
                $skippedCount = $result['skipped_count'];

                Notification::make()
                    ->title('Đã tạo đợt nhập kho từ sản xuất!')
                    ->body("Đợt [{$batch->code}]: Tổng cộng {$count} thiết bị (Thêm mới: {$createdCount}, Cập nhật: {$updatedCount}".($skippedCount > 0 ? ", Bỏ qua: {$skippedCount}" : '').") vào kho {$warehouse->name}. Bấm \"In mã QR\" để in tem dán sản phẩm.")
                    ->success()
                    ->send();

                $this->redirect(CheckinBatchResource::getUrl('edit', ['record' => $batch]));
            });
    }

    public static function getDefaultSheetData(): array
    {
        $headers = [
            'Số Seri',
            'Dòng sản phẩm',
            'Kho lưu trữ',
            'Vị trí',
            'Kích thước',
            'Ngày sản xuất',
            'Nguyên giá',
            'Ghi chú',
        ];

        $sampleRow = [
            'P26-HN-PROD01',
            'P2.6 Sự kiện',
            'Kho Hà Nội',
            'HN-K1',
            '500×500 mm',
            now()->format('d/m/Y'),
            0,
            'Xem tab "Danh mục tham khảo" để copy tên Dòng SP / Kho',
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

        // Build reference sheet data (Dòng sản phẩm, Kho, Mã kho, Vị trí)
        $productLines = ProductLine::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();
        $locations = WarehouseLocation::with('warehouse')->where('is_active', true)->get();

        $refHeaders = [
            0 => ['v' => 'Dòng sản phẩm (Tên)', 's' => ['bl' => 1, 'bg' => ['rgb' => '#DBEAFE'], 'fs' => 11]],
            1 => ['v' => 'Mã dòng SP', 's' => ['bl' => 1, 'bg' => ['rgb' => '#DBEAFE'], 'fs' => 11]],
            2 => ['v' => 'Kho lưu trữ (Tên)', 's' => ['bl' => 1, 'bg' => ['rgb' => '#DCFCE7'], 'fs' => 11]],
            3 => ['v' => 'Mã kho', 's' => ['bl' => 1, 'bg' => ['rgb' => '#DCFCE7'], 'fs' => 11]],
            4 => ['v' => 'Vị trí kho', 's' => ['bl' => 1, 'bg' => ['rgb' => '#FEF3C7'], 'fs' => 11]],
            5 => ['v' => 'Thuộc kho', 's' => ['bl' => 1, 'bg' => ['rgb' => '#FEF3C7'], 'fs' => 11]],
        ];

        $refCellData = [0 => $refHeaders];
        $maxRefRows = max($productLines->count(), $warehouses->count(), $locations->count(), 1);

        for ($r = 0; $r < $maxRefRows; $r++) {
            $rowIdx = $r + 1;
            $pl = $productLines->get($r);
            $wh = $warehouses->get($r);
            $loc = $locations->get($r);

            if ($pl) {
                $refCellData[$rowIdx][0] = ['v' => $pl->name, 's' => ['fs' => 11]];
                $refCellData[$rowIdx][1] = ['v' => $pl->code ?? '', 's' => ['fs' => 11]];
            }
            if ($wh) {
                $refCellData[$rowIdx][2] = ['v' => $wh->name, 's' => ['fs' => 11]];
                $refCellData[$rowIdx][3] = ['v' => $wh->code ?? '', 's' => ['fs' => 11]];
            }
            if ($loc) {
                $refCellData[$rowIdx][4] = ['v' => $loc->name, 's' => ['fs' => 11]];
                $refCellData[$rowIdx][5] = ['v' => $loc->warehouse?->name ?? '', 's' => ['fs' => 11]];
            }
        }

        return [
            'id' => 'workbook-production',
            'sheetOrder' => ['sheet-prod-01', 'sheet-ref-01'],
            'name' => 'Sản xuất LED',
            'appVersion' => '3.0.0-alpha',
            'sheets' => [
                'sheet-prod-01' => [
                    'id' => 'sheet-prod-01',
                    'name' => 'Nhập thiết bị sản xuất',
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
                        7 => ['w' => 240],
                    ],
                ],
                'sheet-ref-01' => [
                    'id' => 'sheet-ref-01',
                    'name' => 'Danh mục tham khảo',
                    'rowCount' => max(50, $maxRefRows + 10),
                    'columnCount' => 8,
                    'cellData' => $refCellData,
                    'columnData' => [
                        0 => ['w' => 200],
                        1 => ['w' => 120],
                        2 => ['w' => 180],
                        3 => ['w' => 100],
                        4 => ['w' => 150],
                        5 => ['w' => 150],
                    ],
                ],
            ],
        ];
    }

    public static function parseSheetData(mixed $sheetData): array
    {
        if (is_string($sheetData)) {
            $sheetData = json_decode($sheetData, true);
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
            } elseif (in_array($slug, ['kho', 'kho_hang', 'kho_luu_tru', 'kho_hien_tai', 'warehouse'])) {
                $columnMap['warehouse'] = $index;
            } elseif (in_array($slug, ['vi_tri', 'vi_tri_kho', 'ke', 'location', 'khu_vuc'])) {
                $columnMap['location'] = $index;
            } elseif (in_array($slug, ['kich_thuoc', 'size', 'quy_cach'])) {
                $columnMap['size'] = $index;
            } elseif (in_array($slug, ['ngay_san_xuat', 'manufactured_date', 'nsx'])) {
                $columnMap['manufactured_date'] = $index;
            } elseif (in_array($slug, ['ngay_du_kien', 'expected_date', 'ngay_nhap_du_kien', 'ngay_du_kien_nhap'])) {
                $columnMap['expected_date'] = $index;
            } elseif (in_array($slug, ['ngay_mua', 'purchase_date'])) {
                $columnMap['purchase_date'] = $index;
            } elseif (in_array($slug, ['nguyen_gia', 'gia_mua', 'purchase_cost', 'gia_tri'])) {
                $columnMap['purchase_cost'] = $index;
            } elseif (in_array($slug, ['ghi_chu', 'note', 'mo_ta'])) {
                $columnMap['note'] = $index;
            }
        }

        return $columnMap;
    }
}
