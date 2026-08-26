<?php

namespace App\Filament\Resources\ProductLines\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductLineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin dòng sản phẩm')
                    ->description('Phân loại dòng module LED và nhà sản xuất')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('name')
                                ->label('Tên dòng sản phẩm')
                                ->required()
                                ->placeholder('VD: P2.5 Indoor High-Refresh'),
                            TextInput::make('code')
                                ->label('Mã dòng sản phẩm')
                                ->required()
                                ->placeholder('VD: P25-IN'),
                            TextInput::make('brand')
                                ->label('Thương hiệu')
                                ->placeholder('VD: Gloshine / Unilumin / Absen'),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('environment')
                                ->label('Môi trường sử dụng')
                                ->options([
                                    'indoor' => 'Trong nhà (Indoor)',
                                    'outdoor' => 'Ngoài trời (Outdoor - Chống nước)',
                                    'semi_outdoor' => 'Bán ngoài trời (Semi-outdoor)',
                                ])
                                ->required()
                                ->default('indoor'),
                            Toggle::make('is_active')
                                ->label('Đang kinh doanh')
                                ->default(true)
                                ->inline(false),
                        ]),
                    ]),

                Section::make('Thông số kỹ thuật Cabinet')
                    ->description('Kích thước module, công suất và trọng lượng phục vụ tính toán tự động')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('pixel_pitch')
                                ->label('Khoảng cách điểm ảnh (Pixel Pitch)')
                                ->numeric()
                                ->suffix('mm')
                                ->placeholder('2.50'),
                            TextInput::make('module_width_mm')
                                ->label('Chiều rộng Cabinet')
                                ->numeric()
                                ->suffix('mm')
                                ->placeholder('500.00'),
                            TextInput::make('module_height_mm')
                                ->label('Chiều cao Cabinet')
                                ->numeric()
                                ->suffix('mm')
                                ->placeholder('500.00'),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('weight_kg')
                                ->label('Trọng lượng / Cabinet')
                                ->numeric()
                                ->suffix('kg')
                                ->placeholder('7.50'),
                            TextInput::make('power_watt')
                                ->label('Công suất tối đa / m²')
                                ->numeric()
                                ->suffix('W')
                                ->placeholder('600.00'),
                            TextInput::make('cabinet_material')
                                ->label('Chất liệu vỏ Cabinet')
                                ->placeholder('VD: Nhôm đúc Die-cast Aluminum'),
                        ]),
                    ]),
            ]);
    }
}
