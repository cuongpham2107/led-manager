<?php

namespace App\Filament\Pages;

use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class RevenueReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo & Thống kê';

    protected static ?string $navigationLabel = 'Báo cáo doanh thu & Lãi lỗ';

    protected static ?string $title = 'Báo Cáo Doanh Thu & Lợi Nhuận Từng Dự Án (Event P&L)';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.revenue-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->with(['customer', 'quotation', 'salesUser', 'contracts.payments'])
            )
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
