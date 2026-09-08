<?php

namespace App\Filament\Resources\ReturnBatches\Pages;

use App\Enums\BatchStatus;
use App\Enums\ReturnBatchStatus;
use App\Filament\Resources\ReturnBatches\ReturnBatchResource;
use App\Models\CheckoutBatch;
use App\Models\ReturnBatch;
use App\Models\ReturnBatchItem;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListReturnBatches extends ListRecords
{
    protected static string $resource = ReturnBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Thêm đợt nhập trả')
                ->icon('heroicon-o-plus')
                ->modalHeading('Thêm đợt nhập trả')
                ->modalWidth(Width::Large)
                ->modalSubmitActionLabel('Lưu & Bắt đầu nhận hàng')
                ->modalCancelActionLabel('Hủy')
                ->successNotificationTitle('Tạo đợt nhập trả thành công!')
                ->schema([
                    Select::make('checkout_batch_id')
                        ->label('Từ đợt xuất kho')
                        ->placeholder('Chọn một đợt xuất kho...')
                        ->options(function () {
                            /** @var User|null $user */
                            $user = Auth::user();
                            $query = CheckoutBatch::query()
                                ->with(['order.customer'])
                                ->withCount('items')
                                ->whereIn('status', [BatchStatus::Dispatched, BatchStatus::InProgress, BatchStatus::Completed])
                                ->has('items')
                                ->whereDoesntHave('returnBatches');

                            if ($whId = $user?->getScopedWarehouseId()) {
                                $query->where('warehouse_id', $whId);
                            }

                            return $query->latest('id')
                                ->get()
                                ->mapWithKeys(function (CheckoutBatch $batch) {
                                    $statusLabel = match ($batch->status) {
                                        BatchStatus::Dispatched => 'Đã xuất kho',
                                        BatchStatus::InProgress => 'Đang xuất kho',
                                        BatchStatus::Completed => 'Đã xuất hoàn tất',
                                        default => $batch->status->getLabel(),
                                    };

                                    $details = [$statusLabel];
                                    if ($batch->order) {
                                        $orderNo = $batch->order->order_no;
                                        $customer = $batch->order->customer?->name;
                                        $details[] = 'Đơn: '.$orderNo.($customer ? " - {$customer}" : '');
                                    } elseif ($batch->purpose) {
                                        $details[] = $batch->purpose;
                                    }
                                    if ($batch->items_count > 0) {
                                        $details[] = "{$batch->items_count} cabin";
                                    }

                                    $label = $batch->code.' ('.implode(' · ', $details).')';

                                    return [$batch->id => $label];
                                });
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    Textarea::make('note')
                        ->label('Ghi chú')
                        ->placeholder('Ghi chú tình trạng thiết bị khi thu hồi...')
                        ->rows(2),
                ])
                ->using(function (array $data): ReturnBatch {
                    return DB::transaction(function () use ($data) {
                        /** @var CheckoutBatch $checkoutBatch */
                        $checkoutBatch = CheckoutBatch::with(['items'])->findOrFail($data['checkout_batch_id']);

                        $code = CodeGeneratorService::generate('RET', 'return_batches');

                        $returnBatch = ReturnBatch::create([
                            'code' => $code,
                            'checkout_batch_id' => $checkoutBatch->id,
                            'return_date' => now()->toDateString(),
                            'note' => $data['note'] ?? null,
                            'status' => ReturnBatchStatus::InProgress,
                            'created_by' => Auth::id(),
                        ]);

                        foreach ($checkoutBatch->items as $cbItem) {
                            ReturnBatchItem::create([
                                'return_batch_id' => $returnBatch->id,
                                'asset_id' => $cbItem->asset_id,
                                'checkout_batch_item_id' => $cbItem->id,
                                'grade' => null,
                                'is_received' => false,
                                'received_by' => null,
                                'received_at' => null,
                            ]);
                        }

                        return $returnBatch;
                    });
                })
                ->after(function ($livewire, ReturnBatch $record) {
                    $livewire->replaceMountedAction('edit', context: [
                        'table' => true,
                        'recordKey' => (string) $record->getKey(),
                    ]);
                }),
        ];
    }
}
