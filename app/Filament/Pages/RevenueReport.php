<?php

namespace App\Filament\Pages;

use App\Models\Order;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class RevenueReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Doanh thu';

    protected static ?string $title = 'Báo Cáo Doanh Thu & Lợi Nhuận Từng Dự Án (Event P&L)';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.revenue-report';

    public function getStats(): array
    {
        $whId = auth()->user()?->getScopedWarehouseId();
        $query = Order::with('quotation');
        if ($whId) {
            $query->where('warehouse_id', $whId);
        }
        $orders = $query->get();
        $totalRevenue = $orders->sum('value');
        $totalCogs = $orders->sum(fn ($o) => (float) ($o->quotation?->total_cost ?? 0));
        $grossProfit = $totalRevenue - $totalCogs;
        $avgMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 1) : 0;
        $totalOrders = $orders->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'avg_margin' => $avgMargin,
            'total_orders' => $totalOrders,
        ];
    }

    public function getTopProjects(): array
    {
        $whId = auth()->user()?->getScopedWarehouseId();
        $query = Order::with(['customer', 'quotation', 'salesUser']);
        if ($whId) {
            $query->where('warehouse_id', $whId);
        }

        return $query
            ->orderByDesc('value')
            ->take(5)
            ->get()
            ->map(function ($order) {
                $rev = (float) $order->value;
                $cogs = (float) ($order->quotation?->total_cost ?? 0);
                $profit = $rev - $cogs;
                $margin = $rev > 0 ? round(($profit / $rev) * 100, 1) : 0;

                return [
                    'order_no' => $order->order_no,
                    'event' => $order->event ?: $order->order_no,
                    'customer' => $order->customer?->name ?? '—',
                    'sales_user' => $order->salesUser?->name ?? '—',
                    'revenue' => $rev,
                    'cogs' => $cogs,
                    'profit' => $profit,
                    'margin' => $margin,
                    'date' => $order->request_date?->format('d/m/Y') ?? '—',
                ];
            })->toArray();
    }

    public function table(Table $table): Table
    {
        $query = Order::query()->with(['customer', 'quotation', 'salesUser', 'contracts']);
        if ($whId = auth()->user()?->getScopedWarehouseId()) {
            $query->where('warehouse_id', $whId);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('order_no')
                    ->label('Mã đơn')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('event')
                    ->label('Sự kiện')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable(),
                TextColumn::make('request_date')
                    ->label('Ngày diễn ra')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Doanh thu (VND)')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('quotation.total_cost')
                    ->label('Giá vốn COGS')
                    ->money('VND')
                    ->state(fn ($record) => $record->quotation?->total_cost ?? 0),
                TextColumn::make('net_profit')
                    ->label('Lợi nhuận gộp')
                    ->money('VND')
                    ->weight('bold')
                    ->color('success')
                    ->state(fn ($record) => ($record->value ?: 0) - ($record->quotation?->total_cost ?: 0)),
                TextColumn::make('quotation.margin_percent')
                    ->label('Biên LN')
                    ->suffix('%')
                    ->badge()
                    ->state(fn ($record) => $record->quotation?->margin_percent ?? 0),
                TextColumn::make('salesUser.name')
                    ->label('Sales'),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Khách hàng')
                    ->relationship('customer', 'name'),
                Filter::make('request_date')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('request_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('request_date', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
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
            'Content-Disposition' => 'attachment; filename="revenue_pnl_report_'.date('Ymd_His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Mã đơn hàng',
                'Tên sự kiện',
                'Khách hàng',
                'Ngày bắt đầu',
                'Doanh thu (VND)',
                'Giá vốn COGS (VND)',
                'Lợi nhuận gộp (VND)',
                'Biên lợi nhuận (%)',
                'Sales phụ trách',
            ]);

            $orders = Order::with(['customer', 'quotation', 'salesUser'])->get();
            foreach ($orders as $order) {
                $cogs = (float) ($order->quotation?->total_cost ?? 0);
                $rev = (float) $order->value;
                $profit = $rev - $cogs;
                $margin = $order->quotation?->margin_percent ?? ($rev > 0 ? round(($profit / $rev) * 100, 2) : 0);

                fputcsv($handle, [
                    $order->order_no,
                    $order->event,
                    $order->customer?->name,
                    $order->request_date?->format('d/m/Y'),
                    $rev,
                    $cogs,
                    $profit,
                    $margin.'%',
                    $order->salesUser?->name,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
