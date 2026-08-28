<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class SalesConversionReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo & Thống kê';

    protected static ?string $navigationLabel = 'Tỷ lệ chuyển đổi Sales';

    protected static ?string $title = 'Báo Cáo Tỷ Lệ Chuyển Đổi Báo Giá & Hiệu Suất Sales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.sales-conversion-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()->whereHas('salesQuotations')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nhân viên Sales')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('total_quotations')
                    ->label('Tổng báo giá')
                    ->numeric()
                    ->sortable()
                    ->state(fn (User $record) => $record->salesQuotations()->count()),
                TextColumn::make('total_quote_value')
                    ->label('Tổng giá trị báo giá')
                    ->money('VND')
                    ->sortable()
                    ->state(fn (User $record) => $record->salesQuotations()->sum('total_price')),
                TextColumn::make('converted_count')
                    ->label('Đã chốt thành công')
                    ->badge()
                    ->color('success')
                    ->state(fn (User $record) => $record->salesQuotations()->where('status', 'converted')->count()),
                TextColumn::make('converted_value')
                    ->label('Doanh thu chốt (VND)')
                    ->money('VND')
                    ->weight('bold')
                    ->color('success')
                    ->state(fn (User $record) => $record->salesQuotations()->where('status', 'converted')->sum('total_price')),
                TextColumn::make('conversion_rate')
                    ->label('Tỷ lệ chuyển đổi')
                    ->badge()
                    ->color(function ($state) {
                        $val = (float) str_replace('%', '', (string) $state);

                        return $val >= 50 ? 'success' : ($val >= 30 ? 'warning' : 'danger');
                    })
                    ->state(function (User $record) {
                        $total = $record->salesQuotations()->count();
                        if ($total === 0) {
                            return '0%';
                        }
                        $won = $record->salesQuotations()->where('status', 'converted')->count();

                        return round(($won / $total) * 100, 1).'%';
                    }),
                TextColumn::make('lost_count')
                    ->label('Thất bại / Hủy')
                    ->badge()
                    ->color('danger')
                    ->state(fn (User $record) => $record->salesQuotations()->whereIn('status', ['rejected', 'expired'])->count()),
                TextColumn::make('pending_count')
                    ->label('Đang theo đuổi')
                    ->badge()
                    ->color('warning')
                    ->state(fn (User $record) => $record->salesQuotations()->whereIn('status', ['draft', 'sent', 'approved'])->count()),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query;
                    }),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Xuất CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->action(fn () => $this->exportCsv()),
            ])
            ->paginated(false);
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="sales_conversion_report_'.date('Ymd_His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Nhân viên Sales',
                'Tổng báo giá',
                'Tổng giá trị báo giá (VND)',
                'Đã chốt thành công',
                'Doanh thu chốt (VND)',
                'Tỷ lệ chuyển đổi (%)',
                'Thất bại / Hủy',
                'Đang theo đuổi',
            ]);

            $users = User::whereHas('salesQuotations')->get();
            foreach ($users as $user) {
                $total = $user->salesQuotations()->count();
                $totalVal = (float) $user->salesQuotations()->sum('total_price');
                $won = $user->salesQuotations()->where('status', 'converted')->count();
                $wonVal = (float) $user->salesQuotations()->where('status', 'converted')->sum('total_price');
                $rate = $total > 0 ? round(($won / $total) * 100, 1) : 0;
                $lost = $user->salesQuotations()->whereIn('status', ['rejected', 'expired'])->count();
                $pending = $user->salesQuotations()->whereIn('status', ['draft', 'sent', 'approved'])->count();

                fputcsv($handle, [
                    $user->name,
                    $total,
                    $totalVal,
                    $won,
                    $wonVal,
                    $rate.'%',
                    $lost,
                    $pending,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
