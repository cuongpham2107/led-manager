<?php

namespace App\Filament\Pages;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class SalesConversionReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Tỷ lệ chuyển đổi Sales';

    protected static ?string $title = 'Báo Cáo Tỷ Lệ Chuyển Đổi Báo Giá & Hiệu Suất Sales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected string $view = 'filament.pages.sales-conversion-report';

    public function getStats(): array
    {
        $quotes = Quotation::all();
        $total = $quotes->count();
        $totalVal = (float) $quotes->sum('total_price');

        $won = $quotes->where('status', QuotationStatus::Converted)->count();
        $wonVal = (float) $quotes->where('status', QuotationStatus::Converted)->sum('total_price');

        $lost = $quotes->whereIn('status', [QuotationStatus::Rejected, QuotationStatus::Expired])->count();
        $lostVal = (float) $quotes->whereIn('status', [QuotationStatus::Rejected, QuotationStatus::Expired])->sum('total_price');

        $pending = $quotes->whereIn('status', [QuotationStatus::Draft, QuotationStatus::Sent, QuotationStatus::Approved])->count();
        $pendingVal = (float) $quotes->whereIn('status', [QuotationStatus::Draft, QuotationStatus::Sent, QuotationStatus::Approved])->sum('total_price');

        $winRate = $total > 0 ? round(($won / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'total_value' => $totalVal,
            'won' => $won,
            'won_value' => $wonVal,
            'lost' => $lost,
            'lost_value' => $lostVal,
            'pending' => $pending,
            'pending_value' => $pendingVal,
            'win_rate' => $winRate,
        ];
    }

    public function getSalesFunnel(): array
    {
        $quotes = Quotation::all();
        $total = max(1, $quotes->count());

        $draft = $quotes->where('status', QuotationStatus::Draft)->count();
        $sent = $quotes->where('status', QuotationStatus::Sent)->count();
        $approved = $quotes->where('status', QuotationStatus::Approved)->count();
        $converted = $quotes->where('status', QuotationStatus::Converted)->count();
        $rejected = $quotes->where('status', QuotationStatus::Rejected)->count();

        return [
            ['label' => 'Báo giá Nháp (Draft)', 'count' => $draft, 'pct' => round(($draft / $total) * 100, 1), 'color' => 'bg-gray-400'],
            ['label' => 'Đã gửi khách (Sent)', 'count' => $sent, 'pct' => round(($sent / $total) * 100, 1), 'color' => 'bg-blue-500'],
            ['label' => 'Khách duyệt (Approved)', 'count' => $approved, 'pct' => round(($approved / $total) * 100, 1), 'color' => 'bg-amber-500'],
            ['label' => 'Chốt thành đơn (Converted)', 'count' => $converted, 'pct' => round(($converted / $total) * 100, 1), 'color' => 'bg-emerald-500'],
            ['label' => 'Từ chối / Hủy (Rejected)', 'count' => $rejected, 'pct' => round(($rejected / $total) * 100, 1), 'color' => 'bg-red-500'],
        ];
    }

    public function getSalesLeaderboard(): array
    {
        return User::whereHas('salesQuotations')
            ->with('salesQuotations')
            ->get()
            ->map(function ($user) {
                $quotes = $user->salesQuotations;
                $total = $quotes->count();
                $won = $quotes->where('status', QuotationStatus::Converted)->count();
                $wonVal = (float) $quotes->where('status', QuotationStatus::Converted)->sum('total_price');
                $winRate = $total > 0 ? round(($won / $total) * 100, 1) : 0;

                return [
                    'name' => $user->name,
                    'avatar' => $user->avatar_url ?? null,
                    'total' => $total,
                    'won' => $won,
                    'won_value' => $wonVal,
                    'win_rate' => $winRate,
                ];
            })
            ->sortByDesc('won_value')
            ->values()
            ->toArray();
    }

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
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
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
