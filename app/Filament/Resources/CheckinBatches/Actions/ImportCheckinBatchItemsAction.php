<?php

namespace App\Filament\Resources\CheckinBatches\Actions;

use App\Enums\AssetStatus;
use App\Imports\CheckinBatchImport;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\ProductLine;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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
                ? "Import thiết bị từ Excel — Đợt: {$record->code}"
                : 'Import thiết bị từ Excel')
            ->modalDescription('Upload file Excel (.xlsx, .xls, .csv) chứa danh sách thiết bị. File cần có ít nhất cột "Số Seri".')
            ->modalSubmitActionLabel('Lưu & Thêm vào đợt nhập')
            ->modalWidth(Width::FiveExtraLarge)
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

                    FileUpload::make('excel_file')
                        ->label('File Excel')
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                        ->downloadable()
                        ->previewable(false)
                        ->required()
                        ->helperText(new HtmlString('Hỗ trợ file .xlsx, .xls, .csv — <a href="'.route('filament.checkin-batch-template').'" target="_blank" class="text-primary-600 underline">Tải file mẫu</a>')),
                ]);
            })
            ->action(function ($livewire, array $data): void {
                /** @var CheckinBatch|null $record */
                $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
                $isExisting = $record instanceof CheckinBatch && $record->exists;

                $import = new CheckinBatchImport;
                Excel::import($import, $data['excel_file']);

                $rows = $import->rows;

                if (empty($rows)) {
                    Notification::make()
                        ->title('File không có dữ liệu')
                        ->body('Vui lòng upload file Excel chứa ít nhất 1 dòng dữ liệu thiết bị.')
                        ->warning()
                        ->send();

                    return;
                }

                $columnMap = static::mapColumns($rows[0] ?? []);
                if (! isset($columnMap['serial_no'])) {
                    Notification::make()
                        ->title('Không tìm thấy cột Số Seri')
                        ->body('File Excel cần có cột "Số Seri" hoặc "serial_no".')
                        ->danger()
                        ->send();

                    return;
                }

                $validRows = [];
                foreach ($rows as $row) {
                    $serialNo = trim((string) ($row[array_keys($row)[$columnMap['serial_no']] ?? ''] ?? ''));
                    if ($serialNo !== '') {
                        $validRows[] = $row;
                    }
                }

                if (empty($validRows)) {
                    Notification::make()
                        ->title('Không có dữ liệu hợp lệ')
                        ->body('File Excel không có dòng nào chứa số Seri.')
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
                    &$addedCount,
                    &$createdCount,
                    &$skippedCount,
                    &$receivedCount
                ) {
                    foreach ($validRows as $row) {
                        $values = array_values($row);
                        $serialNo = trim((string) ($values[$columnMap['serial_no']] ?? ''));
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
                            if (isset($columnMap['product_line']) && ! empty($values[$columnMap['product_line']])) {
                                $plVal = trim((string) $values[$columnMap['product_line']]);
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

                            $size = isset($columnMap['size']) && ! empty($values[$columnMap['size']])
                                ? trim((string) $values[$columnMap['size']])
                                : '500×500 mm';

                            $locationId = $defaultLocationId;
                            if (isset($columnMap['location']) && ! empty($values[$columnMap['location']])) {
                                $locVal = trim((string) $values[$columnMap['location']]);
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
                                'current_warehouse_id' => null,
                                'warehouse_location_id' => null,
                                'size' => $size,
                                'current_status' => AssetStatus::NewlyAdded,
                                'note' => isset($columnMap['note']) ? trim((string) ($values[$columnMap['note']] ?? '')) : null,
                            ]);

                            $isNewlyCreated = true;
                            $createdCount++;
                        }

                        $item = CheckinBatchItem::firstOrNew([
                            'checkin_batch_id' => $record->id,
                            'asset_id' => $asset->id,
                        ]);

                        $isNewItem = ! $item->exists;

                        if (! $item->is_received) {
                            $item->condition = null;
                            $item->is_received = false;
                            $item->received_at = null;
                            $item->received_by = null;
                            $item->save();
                        }

                        if ($isNewItem) {
                            $addedCount++;
                        }
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
                $values = array_values($row);
                $serialNo = trim((string) ($values[$columnMap['serial_no']] ?? ''));
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
                    if (isset($columnMap['product_line']) && ! empty($values[$columnMap['product_line']])) {
                        $plVal = trim((string) $values[$columnMap['product_line']]);
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

                    $size = isset($columnMap['size']) && ! empty($values[$columnMap['size']])
                        ? trim((string) $values[$columnMap['size']])
                        : '500×500 mm';

                    $locationId = $defaultLocationId;
                    if ($warehouseId && isset($columnMap['location']) && ! empty($values[$columnMap['location']])) {
                        $locVal = trim((string) $values[$columnMap['location']]);
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
                        'current_warehouse_id' => null,
                        'warehouse_location_id' => null,
                        'size' => $size,
                        'current_status' => AssetStatus::NewlyAdded,
                        'note' => isset($columnMap['note']) ? trim((string) ($values[$columnMap['note']] ?? '')) : null,
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

    public static function mapColumns(array $firstRow): array
    {
        $columnMap = [];
        $headers = array_values($firstRow);

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
