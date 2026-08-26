<?php

namespace App\Filament\Resources\DeviceTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeviceTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Phân loại danh mục thiết bị')
                    ->description('Cấu hình loại thiết bị, đơn vị tính và quy tắc quản lý Serial')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('name')
                                ->label('Tên loại thiết bị')
                                ->required()
                                ->placeholder('VD: Cabinet LED Module'),
                            TextInput::make('code')
                                ->label('Mã loại thiết bị')
                                ->required()
                                ->placeholder('VD: CAB / PROC / TRUSS'),
                            Select::make('unit')
                                ->label('Đơn vị tính')
                                ->options([
                                    'piece' => 'Tấm / Cái (Piece)',
                                    'set' => 'Bộ (Set)',
                                    'meter' => 'Mét (Meter)',
                                    'roll' => 'Cuộn (Roll)',
                                ])
                                ->required()
                                ->default('piece'),
                        ]),
                        Toggle::make('requires_serial')
                            ->label('Bắt buộc quản lý theo Serial / QR Code (Quét PDA)')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
