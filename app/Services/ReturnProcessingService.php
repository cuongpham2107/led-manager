<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\RepairResultStatus;
use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\Order;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use App\Models\ReturnBatchItem;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnProcessingService
{
    /**
     * Build the grading form schema shared by both return flows
     * (Order return + CheckoutBatch return) so they stay consistent.
     *
     * @return array<int, mixed>
     */
    public static function formSchema(): array
    {
        return [
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
                                ->default(true),
                            Select::make('grade')
                                ->label('Phân loại')
                                ->options(ReturnGrade::class)
                                ->default(ReturnGrade::Normal)
                                ->required(),
                            TextInput::make('grade_note')
                                ->label('Ghi chú lỗi')
                                ->placeholder('Ghi chú hỏng hóc nếu có...'),
                        ]),
                ]),
        ];
    }

    /**
     * Pre-fill the grading items from the given checkout batches.
     *
     * @param  Collection<int, CheckoutBatch>  $checkoutBatches
     * @return array<string, mixed>
     */
    public static function fillFormData(Collection $checkoutBatches): array
    {
        $items = [];

        foreach ($checkoutBatches as $batch) {
            $batch->loadMissing('items.asset.productLine');

            foreach ($batch->items as $cbItem) {
                $asset = $cbItem->asset;
                $items[] = [
                    'asset_id' => $cbItem->asset_id,
                    'asset_name' => $asset
                        ? "{$asset->serial_no} — ".($asset->productLine?->name ?? 'Cabin LED')
                        : "Thiết bị #{$cbItem->asset_id}",
                    'checkout_batch_item_id' => $cbItem->id,
                    'is_received' => true,
                    'grade' => ReturnGrade::Normal->value,
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
    }

    /**
     * Process a return: create the ReturnBatch, grade each item, transition
     * asset statuses (Normal → Ready, Damaged → Repairing + RepairLog,
     * Not received → Missing), log all status changes, complete the involved
     * checkout batches and advance the order when every batch is returned.
     *
     * Shared by the Order return flow and the CheckoutBatch return flow so the
     * two paths can never diverge.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, int>  $completeBatchIds  Explicit checkout batch ids to mark Completed (defaults to batches derived from returned items)
     */
    public function processReturn(
        Order $order,
        array $items,
        ?int $receivedBy = null,
        ?string $returnDate = null,
        ?string $note = null,
        array $completeBatchIds = [],
    ): ReturnBatch {
        return DB::transaction(function () use ($order, $items, $receivedBy, $returnDate, $note, $completeBatchIds) {
            $code = CodeGeneratorService::generate('RET', 'return_batches');
            $receivedBy = $receivedBy ?? Auth::id();

            $returnBatch = ReturnBatch::create([
                'code' => $code,
                'checkout_batch_id' => null,
                'return_date' => $returnDate ?? now()->toDateString(),
                'note' => $note,
                'status' => ReturnBatchStatus::Completed,
                'created_by' => $receivedBy,
                'completed_at' => now(),
            ]);

            foreach ($items as $itemData) {
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
                    'received_by' => $receivedBy,
                    'received_at' => now(),
                ]);

                if (! $isReceived) {
                    if ($asset = Asset::find($assetId)) {
                        $oldStatus = $asset->current_status;
                        $asset->update(['current_status' => AssetStatus::Missing]);

                        AssetStatusLog::create([
                            'asset_id' => $asset->id,
                            'from_status' => $oldStatus,
                            'to_status' => AssetStatus::Missing,
                            'from_warehouse_id' => $asset->current_warehouse_id,
                            'to_warehouse_id' => $order->warehouse_id,
                            'source_type' => ReturnBatch::class,
                            'source_id' => $returnBatch->id,
                            'changed_by' => $receivedBy,
                            'note' => "Thiết bị KHÔNG trả về sau sự kiện '{$order->event}' (Đơn hàng {$order->order_no}). Chuyển sang trạng thái Mất / Chưa trả về.",
                            'created_at' => now(),
                        ]);
                    }

                    continue;
                }

                if ($asset = Asset::find($assetId)) {
                    $oldStatus = $asset->current_status;

                    if ($grade === ReturnGrade::Damaged) {
                        $asset->update(['current_status' => AssetStatus::Repairing]);

                        RepairLog::create([
                            'asset_id' => $asset->id,
                            'start_date' => now()->toDateString(),
                            'repair_note' => "Hỏng hóc sau sự kiện '{$order->event}' (Đơn hàng {$order->order_no}): ".($gradeNote ?: 'Cần kiểm tra kỹ thuật'),
                            'result_status' => RepairResultStatus::Pending,
                            'created_by' => $receivedBy,
                        ]);

                        AssetStatusLog::create([
                            'asset_id' => $asset->id,
                            'from_status' => $oldStatus,
                            'to_status' => AssetStatus::Repairing,
                            'from_warehouse_id' => $asset->current_warehouse_id,
                            'to_warehouse_id' => $order->warehouse_id,
                            'source_type' => ReturnBatch::class,
                            'source_id' => $returnBatch->id,
                            'changed_by' => $receivedBy,
                            'note' => 'Thu hồi sau sự kiện (Hỏng hóc): '.($gradeNote ?: 'Cần bảo dưỡng'),
                            'created_at' => now(),
                        ]);
                    } else {
                        $asset->update(['current_status' => AssetStatus::Ready]);

                        AssetStatusLog::create([
                            'asset_id' => $asset->id,
                            'from_status' => $oldStatus,
                            'to_status' => AssetStatus::Ready,
                            'from_warehouse_id' => $asset->current_warehouse_id,
                            'to_warehouse_id' => $order->warehouse_id,
                            'source_type' => ReturnBatch::class,
                            'source_id' => $returnBatch->id,
                            'changed_by' => $receivedBy,
                            'note' => "Thu hồi sau sự kiện '{$order->event}' — Hoạt động tốt",
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            // Complete the checkout batches involved in this return
            $batchIds = ! empty($completeBatchIds)
                ? $completeBatchIds
                : collect($items)
                    ->pluck('checkout_batch_item_id')
                    ->map(fn ($id) => optional(CheckoutBatchItem::find($id))->checkout_batch_id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

            if (! empty($batchIds)) {
                CheckoutBatch::whereIn('id', $batchIds)->update(['status' => BatchStatus::Completed]);
            }

            // Advance the order to Returned only when ALL its checkout batches are completed
            if ($order->checkoutBatches()->where('status', '!=', BatchStatus::Completed)->doesntExist()) {
                $order->update(['status' => OrderStatus::Returned]);
            }

            return $returnBatch;
        });
    }
}
