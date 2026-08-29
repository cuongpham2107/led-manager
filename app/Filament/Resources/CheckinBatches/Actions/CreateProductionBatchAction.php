<?php

namespace App\Filament\Resources\CheckinBatches\Actions;

use App\Models\DeviceType;
use App\Models\ProductLine;
use App\Models\Warehouse;
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
            ->label('Tạo đợt nhập từ sản xuất')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('success')
            ->modalHeading('Tạo đợt nhập kho từ sản xuất')
            ->modalDescription('Sản xuất xong lô LED? Chỉ cần chọn dòng sản phẩm, loại thiết bị, số lượng, kho nhập — hệ thống tự sinh mã serial, tạo Asset, CheckinBatch, CheckinBatchItem và ghi log nhập kho trong 1 thao tác.')
            ->modalSubmitActionLabel('Tạo đợt nhập')
            ->form([
                Select::make('product_line_id')
                    ->label('Dòng sản phẩm (Product Line)')
                    ->options(ProductLine::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set, Get $get) {
                        if (! $state) {
                            return;
                        }
                        $pl = ProductLine::find($state);
                        if ($pl && blank($get('serial_prefix'))) {
                            $set('serial_prefix', app(ProductionBatchService::class)->suggestSerialPrefix($pl));
                        }
                    }),
                Select::make('device_type_id')
                    ->label('Loại thiết bị (Device Type)')
                    ->options(DeviceType::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity')
                    ->label('Số lượng sản xuất')
                    ->helperText('Tối đa 500 thiết bị / lần')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(500)
                    ->default(10)
                    ->required(),
                Select::make('warehouse_id')
                    ->label('Kho nhập vào')
                    ->options(Warehouse::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->default(fn () => Warehouse::where('is_active', true)->first()?->id)
                    ->required(),
                Grid::make(2)
                    ->schema([
                        TextInput::make('size')
                            ->label('Kích thước cabinet')
                            ->placeholder('VD: 500x500mm, 500x1000mm')
                            ->maxLength(50),
                        TextInput::make('serial_prefix')
                            ->label('Prefix serial_no')
                            ->helperText('Mặc định: {Mã dòng SP}-{YYMMDD}. Có thể override.')
                            ->required()
                            ->maxLength(50),
                    ]),
                Textarea::make('note')
                    ->label('Ghi chú đợt sản xuất')
                    ->rows(2)
                    ->placeholder('VD: Lô P3.91 outdoor sản xuất 29/08, QC đạt chuẩn')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $productLine = ProductLine::findOrFail($data['product_line_id']);
                $deviceType = DeviceType::findOrFail($data['device_type_id']);
                $warehouse = Warehouse::findOrFail($data['warehouse_id']);
                $service = app(ProductionBatchService::class);

                try {
                    $result = $service->createFromProduction(
                        productLine: $productLine,
                        deviceType: $deviceType,
                        quantity: (int) $data['quantity'],
                        warehouse: $warehouse,
                        size: $data['size'] ?? null,
                        serialPrefix: $data['serial_prefix'],
                        note: $data['note'] ?? null,
                        createdBy: Auth::user(),
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

                Notification::make()
                    ->title('Đã tạo đợt nhập kho từ sản xuất!')
                    ->body("Đợt [{$batch->code}]: tạo mới {$count} thiết bị trong kho {$warehouse->name}. Serial từ {$result['assets']->first()->serial_no} đến {$result['assets']->last()->serial_no}.")
                    ->success()
                    ->send();
            });
    }
}
