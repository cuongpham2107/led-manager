<?php

namespace App\Filament\Resources\LedConfigurations\Schemas;

use App\Enums\LedScanMode;
use App\Enums\ProductLineType;
use App\Models\ProductLine;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class LedConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->columnSpanFull()->schema([
                    Select::make('product_line_id')
                        ->label('Dòng sản phẩm LED')
                        ->options(fn () => ProductLine::where('type', ProductLineType::Panel)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('name')
                        ->label('Tên cấu hình')
                        ->placeholder('VD: Lô 09/2026 Novastar')
                        ->required(),
                    TextInput::make('receiving_card')
                        ->label('Card nhận')
                        ->placeholder('VD: Novastar A5s Plus')
                        ->required()
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                            ->where('product_line_id', $get('product_line_id'))
                            ->where('scan_mode', $get('scan_mode'))
                            ->where('controller_model', $get('controller_model')))
                        ->validationMessages(['unique' => 'Dòng sản phẩm này đã có cấu hình trùng card nhận, kiểu quét và đầu phát.']),
                    Select::make('scan_mode')
                        ->label('Kiểu quét')
                        ->options(LedScanMode::class)
                        ->required(),
                    TextInput::make('controller_model')
                        ->label('Đầu phát tương thích')
                        ->placeholder('VD: Novastar VX600')
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Đang sử dụng')
                        ->default(true)
                        ->inline(false),
                ]),
                Textarea::make('note')
                    ->label('Ghi chú')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
