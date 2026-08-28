<?php

namespace App\Filament\Pages;

use App\Enums\RepairResultStatus;
use App\Models\RepairLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class RepairFrequencyReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo & Thống kê';

    protected static ?string $navigationLabel = 'Báo cáo bảo trì & Sự cố';

    protected static ?string $title = 'Báo Cáo Tần Suất Lỗi & Chi Phí Bảo Trì Thiết Bị';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.repair-frequency-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RepairLog::query()->with(['asset.productLine', 'creator'])
            )
            ->columns([
                TextColumn::make('asset.serial_no')
                    ->label('Mã Serial Thiết bị')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('asset.productLine.name')
                    ->label('Dòng LED')
                    ->badge(),
                TextColumn::make('start_date')
                    ->label('Ngày báo lỗi')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Ngày sửa xong')
                    ->date('d/m/Y')
                    ->placeholder('Đang sửa chữa...'),
                TextColumn::make('repair_note')
                    ->label('Nguyên nhân & Nội dung sửa')
                    ->wrap(),
                TextColumn::make('result_status')
                    ->label('Kết quả')
                    ->badge(),
                TextColumn::make('repair_cost')
                    ->label('Chi phí sửa')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('creator.name')
                    ->label('Kỹ thuật viên'),
            ])
            ->filters([
                SelectFilter::make('result_status')
                    ->label('Kết quả xử lý')
                    ->options(RepairResultStatus::class),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Xuất CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->action(fn () => $this->exportCsv()),
            ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="repair_frequency_report_'.date('Ymd_His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Mã Serial',
                'Dòng LED',
                'Ngày báo lỗi',
                'Ngày sửa xong',
                'Nội dung sửa',
                'Kết quả',
                'Chi phí sửa (VND)',
                'Kỹ thuật viên',
            ]);

            $logs = RepairLog::with(['asset.productLine', 'creator'])->get();
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->asset?->serial_no,
                    $log->asset?->productLine?->name,
                    $log->start_date?->format('d/m/Y'),
                    $log->end_date?->format('d/m/Y'),
                    $log->repair_note,
                    $log->result_status?->value ?? '',
                    $log->repair_cost,
                    $log->creator?->name,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
