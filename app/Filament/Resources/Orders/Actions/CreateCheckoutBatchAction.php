<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\LedScanMode;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\LedConfiguration;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductLine;
use App\Services\CodeGeneratorService;
use App\Services\PanelSuggestionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CreateCheckoutBatchAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'create_checkout_batch';
    }

    public static function resolveRequiredQuantity(Order $record): int
    {
        // 1. Tổng số lượng từ các mục chi tiết trong đơn hàng (Order Items)
        $itemsSum = (int) $record->items()->sum('quantity_required');
        if ($itemsSum > 0) {
            return $itemsSum;
        }

        // 2. Nhu cầu từ báo giá liên kết (Quotation)
        if ($record->quotation) {
            if ($record->quotation->estimated_cabinet_qty > 0) {
                return (int) $record->quotation->estimated_cabinet_qty;
            }

            $quoItemsSum = (int) $record->quotation->items()->sum('quantity');
            if ($quoItemsSum > 0) {
                return $quoItemsSum;
            }
        }

        // 3. Tính toán từ diện tích màn hình area_m2
        if ((float) $record->area_m2 > 0) {
            $pl = $record->productLine;
            $cabArea = ($pl && (float) $pl->module_width_mm > 0 && (float) $pl->module_height_mm > 0)
                ? ((float) $pl->module_width_mm / 1000) * ((float) $pl->module_height_mm / 1000)
                : 0.5;

            if ($cabArea > 0) {
                return max(1, (int) round((float) $record->area_m2 / $cabArea));
            }
        }

        return 36;
    }

    /**
     * Dòng BOM là tấm LED và dòng sản phẩm đã khai báo cấu hình → cần chọn cấu hình khi xuất.
     */
    public static function needsConfiguration(OrderItem $item): bool
    {
        $line = $item->productLine;

        return $line && ! $line->isController() && $line->ledConfigurations()->where('is_active', true)->exists();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('CreateCheckoutBatch:Order')
            ->label('Tạo Đợt Xuất Kho')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->modalHeading('Tạo Đợt Xuất Kho')
            ->modalDescription('Khởi tạo phiếu xuất kho, chọn cấu hình LED đúng bộ và tự động phân bổ thiết bị sẵn sàng từ kho.')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Xác nhận & Tạo đợt xuất')
            ->visible(fn (Order $record): bool => ! $record->checkoutBatches()->exists() && in_array($record->status, [OrderStatus::Draft, OrderStatus::OutboundCreated]))
            ->form([
                Placeholder::make('order_summary')
                    ->label('Thông tin đơn hàng & Kho xuất')
                    ->content(fn (Order $record): HtmlString => static::summary($record)),

                Toggle::make('auto_assign_assets')
                    ->label('Tự động chọn & gán thiết bị từ kho')
                    ->default(true)
                    ->live()
                    ->helperText('Hệ thống chỉ gán các tấm cùng một cấu hình LED cho mỗi dòng sản phẩm, ưu tiên cùng lô nhập.'),

                Repeater::make('lines')
                    ->label('Chọn cấu hình LED (đúng bộ)')
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->visible(fn (Get $get, Order $record): bool => (bool) $get('auto_assign_assets') && $record->items->contains(fn (OrderItem $item) => static::needsConfiguration($item)))
                    ->default(fn (Order $record): array => static::defaultLines($record))
                    ->itemLabel(fn (array $state): string => ProductLine::find($state['product_line_id'] ?? null)?->name ?? 'Dòng sản phẩm')
                    ->schema([
                        Hidden::make('order_item_id'),
                        Hidden::make('product_line_id'),
                        Hidden::make('warehouse_id'),
                        Grid::make(3)->schema([
                            Select::make('receiving_card')
                                ->label('Card nhận')
                                ->placeholder('Tất cả')
                                ->options(fn (Get $get): array => static::distinctOptions($get('product_line_id'), 'receiving_card'))
                                ->live(),
                            Select::make('scan_mode')
                                ->label('Kiểu quét')
                                ->placeholder('Tất cả')
                                ->options(fn (Get $get): array => static::distinctOptions($get('product_line_id'), 'scan_mode'))
                                ->live(),
                            Select::make('controller_model')
                                ->label('Đầu phát')
                                ->placeholder('Tất cả')
                                ->options(fn (Get $get): array => static::distinctOptions($get('product_line_id'), 'controller_model'))
                                ->live(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('width_m')
                                ->label('Chiều rộng màn (m)')
                                ->numeric()
                                ->minValue(0.1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::syncQuantityFromSize($get, $set)),
                            TextInput::make('height_m')
                                ->label('Chiều cao màn (m)')
                                ->numeric()
                                ->minValue(0.1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::syncQuantityFromSize($get, $set)),
                            TextInput::make('quantity')
                                ->label('Số tấm cần xuất')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->live(onBlur: true),
                        ]),
                        Select::make('led_configuration_id')
                            ->label('Cấu hình gợi ý')
                            ->helperText('Cấu hình đủ số tấm được xếp lên đầu. Chỉ gán tấm thuộc cấu hình đã chọn.')
                            ->options(fn (Get $get): array => static::suggestionOptions($get))
                            ->required(),
                    ]),

                TextInput::make('quantity')
                    ->label('Số lượng thiết bị cần gán')
                    ->default(fn (Order $record): int => static::resolveRequiredQuantity($record))
                    ->numeric()
                    ->minValue(1)
                    ->step(1)
                    ->visible(fn (Get $get, Order $record): bool => (bool) $get('auto_assign_assets') && $record->items->isEmpty())
                    ->helperText(fn (Order $record): string => 'Đề xuất theo định mức/diện tích đơn hàng: '.static::resolveRequiredQuantity($record).' thiết bị.'),

                Toggle::make('mark_dispatched')
                    ->label('Đánh dấu đã kiểm đếm xong (Scan to Dispatch)')
                    ->default(false)
                    ->visible(fn (Get $get): bool => (bool) $get('auto_assign_assets'))
                    ->helperText('Nếu bật, toàn bộ thiết bị sẽ được đánh dấu đã kiểm đếm xong (is_dispatched = true) và chuyển sang trạng thái Đang vận chuyển.'),
            ])
            ->action(function (Order $record, array $data): void {
                $code = CodeGeneratorService::generate('OUT', 'checkout_batches');
                $autoAssign = (bool) ($data['auto_assign_assets'] ?? true);
                $markDispatched = (bool) ($data['mark_dispatched'] ?? false);

                $plan = $autoAssign
                    ? static::planAssignment($record, $data)
                    : ['assets' => collect(), 'required' => 0, 'warnings' => []];

                static::persist($record, $code, $markDispatched, $plan['assets']);
                static::notify($code, $record, $autoAssign, $plan);
            });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function defaultLines(Order $record): array
    {
        $service = app(PanelSuggestionService::class);

        return $record->items
            ->filter(fn (OrderItem $item) => static::needsConfiguration($item))
            ->map(function (OrderItem $item) use ($record, $service): array {
                $qty = max(1, (int) $item->quantity_required);
                $configId = $item->led_configuration_id
                    ?? $service->suggest($item->productLine, (int) $record->warehouse_id, $qty)->first()['configuration']?->id;

                return [
                    'order_item_id' => $item->id,
                    'product_line_id' => $item->product_line_id,
                    'warehouse_id' => $record->warehouse_id,
                    'quantity' => $qty,
                    'led_configuration_id' => $configId,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function distinctOptions(mixed $productLineId, string $column): array
    {
        if (! $productLineId) {
            return [];
        }

        return LedConfiguration::query()
            ->where('product_line_id', $productLineId)
            ->where('is_active', true)
            ->orderBy($column)
            ->toBase()
            ->distinct()
            ->pluck($column)
            ->mapWithKeys(fn (string $value) => [$value => $column === 'scan_mode' ? (LedScanMode::tryFrom($value)?->getLabel() ?? $value) : $value])
            ->all();
    }

    protected static function syncQuantityFromSize(Get $get, Set $set): void
    {
        $line = ProductLine::find($get('product_line_id'));
        $width = (float) $get('width_m');
        $height = (float) $get('height_m');

        if ($line && $width > 0 && $height > 0) {
            $set('quantity', app(PanelSuggestionService::class)->gridFor($line, $width, $height)['required']);
        }
    }

    /**
     * @return array<int, string>
     */
    protected static function suggestionOptions(Get $get): array
    {
        $line = ProductLine::find($get('product_line_id'));
        if (! $line || ! $get('warehouse_id')) {
            return [];
        }

        $required = max(1, (int) $get('quantity'));
        $filters = [
            'receiving_card' => $get('receiving_card'),
            'scan_mode' => $get('scan_mode'),
            'controller_model' => $get('controller_model'),
        ];

        return app(PanelSuggestionService::class)
            ->suggest($line, (int) $get('warehouse_id'), $required, $filters)
            ->mapWithKeys(fn (array $option) => [
                $option['configuration']->id => sprintf(
                    '%s %s — còn %d/%d tấm%s',
                    $option['is_enough'] ? '✅' : '⚠️',
                    $option['configuration']->label,
                    $option['available'],
                    $required,
                    count($option['lots']) > 1 ? ' ('.count($option['lots']).' lô)' : '',
                ),
            ])
            ->all();
    }

    /**
     * Chọn thiết bị cho từng dòng BOM. Tấm LED chỉ lấy đúng một cấu hình; không lấy bù dòng/cấu hình khác.
     *
     * @param  array<string, mixed>  $data
     * @return array{assets: Collection<int, array{asset: Asset, note: string}>, required: int, warnings: array<int, string>}
     */
    protected static function planAssignment(Order $record, array $data): array
    {
        $service = app(PanelSuggestionService::class);
        $warehouseId = (int) $record->warehouse_id;
        $lines = collect($data['lines'] ?? [])->keyBy(fn (array $line) => (int) ($line['order_item_id'] ?? 0));

        $assigned = collect();
        $pickedIds = [];
        $required = 0;
        $warnings = [];

        foreach ($record->items as $item) {
            $line = $lines->get($item->id, []);
            $needed = (int) ($line['quantity'] ?? $item->quantity_required);
            if ($needed <= 0) {
                continue;
            }
            $required += $needed;
            $note = $item->note ?? "Gán theo định mức đơn hàng {$record->order_no}";

            $configuration = static::resolveConfiguration($item, $line, $warehouseId, $needed);

            if ($configuration) {
                $pick = $service->pickAssets($configuration, $warehouseId, $needed, $pickedIds);
                $assets = $pick['assets'];
                $item->update(['led_configuration_id' => $configuration->id]);

                if ($pick['mixed_lots']) {
                    $warnings[] = "{$item->productLine?->name}: không đủ tấm trong một lô, đã ghép nhiều lô cùng cấu hình \"{$configuration->label}\" — kiểm tra màu sắc khi lắp.";
                }
            } else {
                $assets = PanelSuggestionService::availableQuery($warehouseId)
                    ->when($item->product_line_id, fn ($q, $plId) => $q->where('product_line_id', $plId))
                    ->when($pickedIds !== [], fn ($q) => $q->whereNotIn('id', $pickedIds))
                    ->take($needed)
                    ->get();
            }

            if ($assets->count() < $needed) {
                $warnings[] = "{$item->productLine?->name}: chỉ gán được {$assets->count()}/{$needed} thiết bị.";
            }

            foreach ($assets as $asset) {
                $pickedIds[] = $asset->id;
                $assigned->push(['asset' => $asset, 'note' => $note]);
            }
        }

        // Đơn chưa có dòng BOM: gán theo dòng sản phẩm chính của đơn (nếu có)
        if ($record->items->isEmpty()) {
            $required = (int) ($data['quantity'] ?? static::resolveRequiredQuantity($record));
            PanelSuggestionService::availableQuery($warehouseId)
                ->when($record->product_line_id, fn ($q, $plId) => $q->where('product_line_id', $plId))
                ->take($required)
                ->get()
                ->each(fn (Asset $asset) => $assigned->push(['asset' => $asset, 'note' => "Tự động gán xuất kho cho đơn hàng {$record->order_no}"]));
        }

        return ['assets' => $assigned, 'required' => $required, 'warnings' => $warnings];
    }

    /**
     * @param  array<string, mixed>  $line
     */
    protected static function resolveConfiguration(OrderItem $item, array $line, int $warehouseId, int $needed): ?LedConfiguration
    {
        if (! static::needsConfiguration($item)) {
            return null;
        }

        $configurationId = $line['led_configuration_id'] ?? $item->led_configuration_id;
        if ($configurationId) {
            return LedConfiguration::find($configurationId);
        }

        $best = app(PanelSuggestionService::class)->suggest($item->productLine, $warehouseId, $needed)->first();

        return $best['configuration'] ?? null;
    }

    /**
     * @param  Collection<int, array{asset: Asset, note: string}>  $assignedAssets
     */
    protected static function persist(Order $record, string $code, bool $markDispatched, Collection $assignedAssets): void
    {
        DB::transaction(function () use ($record, $code, $markDispatched, $assignedAssets): void {
            $batch = CheckoutBatch::create([
                'code' => $code,
                'order_id' => $record->id,
                'customer_id' => $record->customer_id,
                'warehouse_id' => $record->warehouse_id,
                'required_area_m2' => $record->area_m2,
                'expected_return_date' => $record->expected_return_date,
                'status' => ($assignedAssets->isNotEmpty() && $markDispatched) ? BatchStatus::InProgress : BatchStatus::Pending,
                'created_by' => Auth::id(),
            ]);

            foreach ($assignedAssets as $entry) {
                /** @var Asset $asset */
                $asset = $entry['asset'];

                CheckoutBatchItem::create([
                    'checkout_batch_id' => $batch->id,
                    'asset_id' => $asset->id,
                    'is_dispatched' => $markDispatched,
                    'dispatched_by' => $markDispatched ? Auth::id() : null,
                    'dispatched_at' => $markDispatched ? now() : null,
                    'note' => $entry['note'],
                ]);

                if ($markDispatched) {
                    $oldStatus = $asset->current_status;
                    $asset->update(['current_status' => AssetStatus::InTransit]);

                    AssetStatusLog::create([
                        'asset_id' => $asset->id,
                        'from_status' => $oldStatus,
                        'to_status' => AssetStatus::InTransit,
                        'from_warehouse_id' => $asset->current_warehouse_id,
                        'to_warehouse_id' => $batch->warehouse_id,
                        'source_type' => CheckoutBatch::class,
                        'source_id' => $batch->id,
                        'changed_by' => Auth::id(),
                        'note' => "Tự động gán & chuẩn bị xuất kho: Đợt {$code} (Đơn hàng {$record->order_no})",
                        'created_at' => now(),
                    ]);
                }
            }

            $record->update(['status' => OrderStatus::OutboundCreated]);
            $record->refresh();
        });
    }

    /**
     * @param  array{assets: Collection<int, array{asset: Asset, note: string}>, required: int, warnings: array<int, string>}  $plan
     */
    protected static function notify(string $code, Order $record, bool $autoAssign, array $plan): void
    {
        $assignedCount = $plan['assets']->count();
        $notification = Notification::make();

        if (! $autoAssign) {
            $notification->title('Đã tạo phiếu xuất kho!')
                ->body("Phiếu xuất kho {$code} cho đơn hàng {$record->order_no} đã được tạo thành công.")
                ->success()
                ->send();

            return;
        }

        $warnings = $plan['warnings'] === [] ? '' : ' '.implode(' ', $plan['warnings']);

        if ($assignedCount === 0) {
            $notification->title('Đã tạo phiếu xuất kho (Chưa gán được thiết bị)!')
                ->body("Phiếu xuất kho {$code} đã được tạo nhưng kho hiện không có thiết bị phù hợp để gán.{$warnings}")
                ->warning();
        } elseif ($plan['warnings'] !== []) {
            $notification->title('Đã tạo phiếu xuất kho (Cần kiểm tra)!')
                ->body("Phiếu xuất kho {$code} đã gán {$assignedCount}/{$plan['required']} thiết bị.{$warnings}")
                ->warning();
        } else {
            $notification->title('Đã tạo phiếu xuất kho & gán thiết bị!')
                ->body("Phiếu xuất kho {$code} đã được tạo thành công và tự động gán đủ {$assignedCount} thiết bị đúng bộ từ kho.")
                ->success();
        }

        $notification->send();
    }

    protected static function summary(Order $record): HtmlString
    {
        $requiredQty = static::resolveRequiredQuantity($record);
        $warehouseName = e($record->warehouse?->name ?? 'Chưa xác định');
        $plName = e($record->productLine?->name ?? 'Theo danh mục đơn hàng');
        $readyCount = PanelSuggestionService::availableQuery((int) $record->warehouse_id)->count();

        $stockBadgeClass = $readyCount >= $requiredQty
            ? 'text-emerald-600 dark:text-emerald-400 font-semibold'
            : 'text-amber-600 dark:text-amber-400 font-semibold';

        return new HtmlString("
            <div class=\"rounded-lg border border-gray-200 bg-gray-50/50 p-3.5 text-sm dark:border-gray-700 dark:bg-gray-800/50 space-y-1.5\">
                <div class=\"flex justify-between\">
                    <span class=\"text-gray-500 dark:text-gray-400\">Đơn hàng:</span>
                    <span class=\"font-medium text-gray-900 dark:text-gray-100\">{$record->order_no}</span>
                </div>
                <div class=\"flex justify-between\">
                    <span class=\"text-gray-500 dark:text-gray-400\">Kho xuất hàng:</span>
                    <span class=\"font-medium text-gray-900 dark:text-gray-100\">{$warehouseName}</span>
                </div>
                <div class=\"flex justify-between\">
                    <span class=\"text-gray-500 dark:text-gray-400\">Dòng sản phẩm:</span>
                    <span class=\"font-medium text-gray-900 dark:text-gray-100\">{$plName}</span>
                </div>
                <div class=\"flex justify-between border-t border-gray-200/80 pt-1.5 dark:border-gray-700/80\">
                    <span class=\"text-gray-500 dark:text-gray-400\">Nhu cầu xuất:</span>
                    <span class=\"font-bold text-primary-600 dark:text-primary-400\">{$requiredQty} thiết bị</span>
                </div>
                <div class=\"flex justify-between\">
                    <span class=\"text-gray-500 dark:text-gray-400\">Tồn kho sẵn sàng (mọi dòng):</span>
                    <span class=\"{$stockBadgeClass}\">{$readyCount} thiết bị khả dụng</span>
                </div>
            </div>
        ");
    }
}
