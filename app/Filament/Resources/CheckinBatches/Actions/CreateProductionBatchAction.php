<?php

namespace App\Filament\Resources\CheckinBatches\Actions;

use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use App\Models\ProductLine;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\ProductionBatchService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Auth;

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
            ->icon('heroicon-o-cog-6-tooth')
            ->color('success')
            ->modalHeading('Tạo đợt nhập kho từ sản xuất / Lô thiết bị mới')
            ->modalDescription('Sản xuất hoặc tạo lô thiết bị mới? Hệ thống tự sinh mã serial, tạo Asset, CheckinBatch, CheckinBatchItem và ghi log nhập kho trong 1 thao tác.')
            ->modalSubmitActionLabel('Tạo đợt nhập')
            ->form([
                Select::make('product_line_id')
                    ->label('Dòng sản phẩm (áp dụng cho Cabinet LED)')
                    ->options(ProductLine::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set, Get $get) {
                        $pl = $state ? ProductLine::find($state) : null;
                        $set('serial_prefix', app(ProductionBatchService::class)->suggestSerialPrefix($pl));
                    }),
                Grid::make(2)
                    ->schema([
                        TextInput::make('quantity')
                            ->label('Số lượng sản xuất / nhập mới')
                            ->helperText('Tối đa 500 thiết bị / lần')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->default(10)
                            ->required(),
                        TextInput::make('size')
                            ->label('Kích thước / Quy cách')
                            ->placeholder('VD: 500x500mm, 1U Rack, 2m, 10m 3 pha...')
                            ->maxLength(50),
                    ]),
                Grid::make(2)
                    ->schema([
                        Select::make('warehouse_id')
                            ->label('Kho nhập vào')
                            ->options(Warehouse::query()->where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->getScopedWarehouseId() ?? Warehouse::where('is_active', true)->first()?->id)
                            ->disabled(fn () => (bool) auth()->user()?->getScopedWarehouseId())
                            ->dehydrated()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('warehouse_location_id', null))
                            ->required(),
                        Select::make('warehouse_location_id')
                            ->label('Vị trí trong kho')
                            ->placeholder('Chọn vị trí kho (tùy chọn)...')
                            ->options(function (Get $get) {
                                $whId = $get('warehouse_id') ?: auth()->user()?->getScopedWarehouseId();
                                if (! $whId) {
                                    return [];
                                }

                                return WarehouseLocation::where('warehouse_id', $whId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Vị trí / zone lưu trữ cụ thể trong kho'),
                    ]),
                TextInput::make('serial_prefix')
                    ->label('Tiền tố mã Serial')
                    ->helperText('Mặc định: {Mã SP|Loại}-{YYMMDD}. Có thể tuỳ chỉnh.')
                    ->required()
                    ->maxLength(50),
                Textarea::make('note')
                    ->label('Ghi chú đợt sản xuất')
                    ->rows(2)
                    ->placeholder('VD: Lô sản xuất ngày 29/08, kiểm tra QC đạt chuẩn...')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $productLine = ! empty($data['product_line_id']) ? ProductLine::find($data['product_line_id']) : null;
                $warehouse = Warehouse::findOrFail($data['warehouse_id']);
                $locationId = ! empty($data['warehouse_location_id']) ? (int) $data['warehouse_location_id'] : null;
                $service = app(ProductionBatchService::class);

                try {
                    $result = $service->createFromProduction(
                        productLine: $productLine,
                        quantity: (int) $data['quantity'],
                        warehouse: $warehouse,
                        size: $data['size'] ?? null,
                        serialPrefix: $data['serial_prefix'],
                        note: $data['note'] ?? null,
                        createdBy: Auth::user(),
                        warehouseLocation: $locationId,
                    );
                } catch (\InvalidArgumentException $e) {
                    Notification::make()
                        ->title('Không thể tạo đợt nhập')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                } catch (\RuntimeException $e) {
                    Notification::make()
                        ->title('Trùng mã serial — đã huỷ')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $batch = $result['batch'];
                $count = $result['assets']->count();
                $location = $locationId ? WarehouseLocation::find($locationId) : null;
                $locInfo = $location ? " • Vị trí: {$location->name}" : '';

                Notification::make()
                    ->title('Đã tạo đợt nhập kho từ sản xuất!')
                    ->body("Đợt [{$batch->code}]: tạo mới {$count} thiết bị trong kho {$warehouse->name}{$locInfo}. Serial từ {$result['assets']->first()->serial_no} đến {$result['assets']->last()->serial_no}. Bấm \"In mã QR\" để in tem dán sản phẩm.")
                    ->success()
                    ->send();

                // Redirect to edit page where user can print QR labels
                $this->redirect(CheckinBatchResource::getUrl('edit', ['record' => $batch]));
            });
    }
}
