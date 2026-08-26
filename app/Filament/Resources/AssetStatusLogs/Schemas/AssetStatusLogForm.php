<?php

namespace App\Filament\Resources\AssetStatusLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetStatusLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nhật ký thay đổi trạng thái & luân chuyển')
                    ->description('Lưu vết chi tiết lịch sử trạng thái thiết bị LED qua các sự kiện')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('asset_id')
                                ->label('Thiết bị (Serial No)')
                                ->relationship('asset', 'serial_no')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('from_status')
                                ->label('Từ trạng thái')
                                ->options([
                                    'ready' => 'Sẵn sàng (Ready)',
                                    'in_event' => 'Đang chạy sự kiện (In Event)',
                                    'in_transit' => 'Đang vận chuyển (In Transit)',
                                    'repairing' => 'Đang bảo dưỡng (Repairing)',
                                    'disposed' => 'Đã thanh lý (Disposed)',
                                ]),
                            Select::make('to_status')
                                ->label('Sang trạng thái')
                                ->options([
                                    'ready' => 'Sẵn sàng (Ready)',
                                    'in_event' => 'Đang chạy sự kiện (In Event)',
                                    'in_transit' => 'Đang vận chuyển (In Transit)',
                                    'repairing' => 'Đang bảo dưỡng (Repairing)',
                                    'disposed' => 'Đã thanh lý (Disposed)',
                                ])
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('from_warehouse_id')
                                ->label('Từ kho')
                                ->relationship('fromWarehouse', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('to_warehouse_id')
                                ->label('Đến kho')
                                ->relationship('toWarehouse', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('changed_by')
                                ->label('Người thực hiện')
                                ->relationship('changer', 'name')
                                ->searchable()
                                ->preload(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('source_type')
                                ->label('Nguồn phát sinh (Model Class)'),
                            TextInput::make('source_id')
                                ->label('ID Nguồn phát sinh'),
                        ]),
                        Textarea::make('note')
                            ->label('Ghi chú luân chuyển')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
