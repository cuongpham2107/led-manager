<?php

namespace App\Filament\Resources\ProductLines\Tables;

use App\Enums\ProductEnvironment;
use App\Models\ProductLine;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductLinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Dòng LED')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->summarize(
                        Count::make()
                            ->label('Tổng dòng sản phẩm'),
                    ),
                TextColumn::make('code')
                    ->label('Mã dòng')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pixel_pitch')
                    ->label('Pixel Pitch')
                    ->formatStateUsing(fn ($state) => "P{$state} mm")
                    ->sortable(),
                TextColumn::make('environment')
                    ->label('Môi trường')
                    ->badge()
                    ->sortable(),
                TextColumn::make('size_display')
                    ->label('Kích thước Cabinet')
                    ->state(fn (ProductLine $record): string => ($record->module_width_mm / 1000).'×'.($record->module_height_mm / 1000).' m ('.(int) $record->module_width_mm.'×'.(int) $record->module_height_mm.'mm)'),
                TextColumn::make('weight_kg')
                    ->label('Trọng lượng')
                    ->suffix(' kg/tấm')
                    ->sortable(),
                TextColumn::make('power_watt')
                    ->label('Công suất')
                    ->suffix(' W/tấm')
                    ->sortable(),
                TextColumn::make('brand')
                    ->label('Thương hiệu')
                    ->searchable()
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label('Hoạt động')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('environment')
                    ->label('Môi trường sử dụng')
                    ->options(ProductEnvironment::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Cập nhật dòng sản phẩm LED')
                    ->modalDescription('Chỉnh sửa thông số kỹ thuật và cấu hình module của dòng LED.')
                    ->modalWidth(Width::FiveExtraLarge),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
