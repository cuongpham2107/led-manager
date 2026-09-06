<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\RepairResultStatus;
use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\Order;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use App\Models\ReturnBatchItem;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'return_order';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('Return:Order')
            ->label('Thu hồi trả kho')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('primary')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Dispatched && $record->checkoutBatches->isNotEmpty())
            ->modalHeading(fn (Order $record) => 'Thu hồi & Trả kho: '.$record->order_no.($record->event ? " ({$record->event})" : ''))
            ->modalDescription('Kiểm đếm thiết bị trả về từ sự kiện, phân loại tình trạng hoạt động (Grading) để tự động cập nhật kho và tạo phiếu bảo dưỡng nếu có hỏng hóc.')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitActionLabel('Xác nhận Nhập trả kho')
            ->fillForm(function (Order $record): array {
                $checkoutBatches = $record->checkoutBatches()->with('items.asset.productLine')->get();
                $items = [];
                foreach ($checkoutBatches as $checkoutBatch) {
                    foreach ($checkoutBatch->items as $cbItem) {
                        $asset = $cbItem->asset;
                        $items[] = [
                            'asset_id' => $cbItem->asset_id,
                            'asset_name' => $asset ? "{$asset->serial_no} — ".($asset->productLine?->name ?? 'Cabin LED') : "Thiết bị #{$cbItem->asset_id}",
                            'checkout_batch_item_id' => $cbItem->id,
                            'is_received' => false,
                            'grade' => null,
                            'grade_note' => null,
                        ];
                    }
                }

                return [
                    'return_date' => now()->toDateString(),
                    'received_by' => Auth::id(),
                    'note' => null,
                    'items' => $items,
                ];
            })
            ->form([
                Grid::make(2)->schema([
                    DatePicker::make('return_date')
                        ->label('Ngày trả thực tế')
                        ->default(now()->toDateString())
                        ->required()
                        ->native(false),
                    Select::make('received_by')
                        ->label('Người tiếp nhận kho')
                        ->options(User::pluck('name', 'id'))
                        ->default(fn () => Auth::id())
                        ->searchable()
                        ->required(),
                ]),
                Textarea::make('note')
                    ->label('Ghi chú tình trạng lô hàng trả về')
                    ->placeholder('VD: Thiết bị tháo dỡ nguyên vẹn, 1 cabin bị chết bóng LED khi làm sự kiện...')
                    ->rows(2),
                Section::make('Danh sách kiểm đếm thiết bị (Grading & Quality Check)')
                    ->description('Đánh dấu các thiết bị đã về kho và chọn trạng thái Bình thường hoặc Hỏng hóc để chuyển sang bộ phận kỹ thuật.')
                    ->schema([
                        Repeater::make('items')
                            ->label('Thiết bị')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->table([
                                TableColumn::make('Thiết bị / Mã Serial'),
                                TableColumn::make('Đã nhận kho'),
                                TableColumn::make('Tình trạng (Grade)'),
                                TableColumn::make('Ghi chú lỗi nếu hỏng'),
                            ])
                            ->schema([
                                Hidden::make('asset_id'),
                                Hidden::make('checkout_batch_item_id'),
                                TextInput::make('asset_name')
                                    ->label('Thiết bị')
                                    ->disabled()
                                    ->dehydrated(false),
                                Toggle::make('is_received')
                                    ->label('Đã nhận')
                                    ->default(false),
                                Select::make('grade')
                                    ->label('Phân loại')
                                    ->options(ReturnGrade::class)
                                    ->required(),
                                TextInput::make('grade_note')
                                    ->label('Ghi chú lỗi')
                                    ->placeholder('Ghi chú hỏng hóc nếu có...'),
                            ]),
                    ]),
            ])
            ->action(function (Order $record, array $data): void {
                DB::transaction(function () use ($record, $data) {
                    $returnBatchCount = ReturnBatch::count() + 1;
                    $code = 'RET-'.date('ym').'-'.str_pad((string) $returnBatchCount, 2, '0', STR_PAD_LEFT);
                    while (ReturnBatch::where('code', $code)->exists()) {
                        $returnBatchCount++;
                        $code = 'RET-'.date('ym').'-'.str_pad((string) $returnBatchCount, 2, '0', STR_PAD_LEFT);
                    }

                    $returnBatch = ReturnBatch::create([
                        'code' => $code,
                        'checkout_batch_id' => null,
                        'return_date' => $data['return_date'] ?? now()->toDateString(),
                        'note' => $data['note'] ?? null,
                        'status' => ReturnBatchStatus::Completed,
                        'created_by' => $data['received_by'] ?? Auth::id(),
                        'completed_at' => now(),
                    ]);

                    $damagedCount = 0;
                    $normalCount = 0;
                    $missingCount = 0;

                    foreach ($data['items'] as $itemData) {
                        $assetId = $itemData['asset_id'];
                        $isReceived = (bool) ($itemData['is_received'] ?? true);
                        $grade = is_string($itemData['grade']) ? ReturnGrade::tryFrom($itemData['grade']) : $itemData['grade'];
                        $gradeNote = $itemData['grade_note'] ?? null;
                        $checkoutBatchItemId = $itemData['checkout_batch_item_id'] ?? null;

                        ReturnBatchItem::create([
                            'return_batch_id' => $returnBatch->id,
                            'asset_id' => $assetId,
                            'checkout_batch_item_id' => $checkoutBatchItemId,
                            'grade' => $grade ?? ReturnGrade::Normal,
                            'grade_note' => $gradeNote,
                            'is_received' => $isReceived,
                            'received_by' => $data['received_by'] ?? Auth::id(),
                            'received_at' => now(),
                        ]);

                        if (! $isReceived) {
                            // Thiết bị không trả về → chuyển sang trạng thái Mất / Chưa trả về
                            if ($asset = Asset::find($assetId)) {
                                $missingCount++;
                                $oldStatus = $asset->current_status;
                                $asset->update(['current_status' => AssetStatus::Missing]);

                                AssetStatusLog::create([
                                    'asset_id' => $asset->id,
                                    'from_status' => $oldStatus,
                                    'to_status' => AssetStatus::Missing,
                                    'from_warehouse_id' => $asset->current_warehouse_id,
                                    'to_warehouse_id' => $record->warehouse_id,
                                    'source_type' => ReturnBatch::class,
                                    'source_id' => $returnBatch->id,
                                    'changed_by' => Auth::id(),
                                    'note' => "Thiết bị KHÔNG trả về sau sự kiện '{$record->event}' (Đơn hàng {$record->order_no}). Chuyển sang trạng thái Mất / Chưa trả về.",
                                    'created_at' => now(),
                                ]);
                            }

                            continue;
                        }

                        if ($asset = Asset::find($assetId)) {
                            $oldStatus = $asset->current_status;

                            if ($grade === ReturnGrade::Damaged) {
                                $damagedCount++;
                                $asset->update(['current_status' => AssetStatus::Repairing]);

                                RepairLog::create([
                                    'asset_id' => $asset->id,
                                    'start_date' => now()->toDateString(),
                                    'repair_note' => "Hỏng hóc sau sự kiện '{$record->event}' (Đơn hàng {$record->order_no}): ".($gradeNote ?: 'Cần kiểm tra kỹ thuật'),
                                    'result_status' => RepairResultStatus::Pending,
                                    'created_by' => Auth::id(),
                                ]);

                                AssetStatusLog::create([
                                    'asset_id' => $asset->id,
                                    'from_status' => $oldStatus,
                                    'to_status' => AssetStatus::Repairing,
                                    'from_warehouse_id' => $asset->current_warehouse_id,
                                    'to_warehouse_id' => $record->warehouse_id,
                                    'source_type' => ReturnBatch::class,
                                    'source_id' => $returnBatch->id,
                                    'changed_by' => Auth::id(),
                                    'note' => 'Thu hồi sau sự kiện (Hỏng hóc): '.($gradeNote ?: 'Cần bảo dưỡng'),
                                    'created_at' => now(),
                                ]);
                            } else {
                                $normalCount++;
                                $asset->update(['current_status' => AssetStatus::Ready]);

                                AssetStatusLog::create([
                                    'asset_id' => $asset->id,
                                    'from_status' => $oldStatus,
                                    'to_status' => AssetStatus::Ready,
                                    'from_warehouse_id' => $asset->current_warehouse_id,
                                    'to_warehouse_id' => $record->warehouse_id,
                                    'source_type' => ReturnBatch::class,
                                    'source_id' => $returnBatch->id,
                                    'changed_by' => Auth::id(),
                                    'note' => "Thu hồi sau sự kiện '{$record->event}' — Hoạt động tốt",
                                    'created_at' => now(),
                                ]);
                            }
                        }
                    }

                    $record->checkoutBatches()->update(['status' => BatchStatus::Completed]);

                    $record->update(['status' => OrderStatus::Returned]);

                    $body = "Phiếu trả kho {$code} đã hoàn tất: {$normalCount} thiết bị đạt chuẩn sẵn sàng trong kho";
                    if ($damagedCount > 0) {
                        $body .= ", {$damagedCount} thiết bị chuyển bảo dưỡng (đã tạo phiếu sửa chữa)";
                    }

                    if ($missingCount > 0) {
                        $body .= ", {$missingCount} thiết bị KHÔNG trả về (đã đánh dấu Mất)";
                    }

                    $body .= '.';

                    Notification::make()
                        ->title('Thu hồi trả kho thành công!')
                        ->body($body)
                        ->success()
                        ->send();
                });
            });
    }
}
