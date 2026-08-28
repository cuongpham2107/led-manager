<?php

namespace App\Providers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $datePicker
                ->displayFormat('d/m/Y')
                ->native(false);
        });

        DateTimePicker::configureUsing(function (DateTimePicker $dateTimePicker): void {
            $dateTimePicker
                ->displayFormat('d/m/Y H:i')
                ->native(false);
        });

        EditAction::configureUsing(function (EditAction $action): void {
            $action->button()->iconButton();
        });

        Table::configureUsing(function (Table $table): void {
            $table
                ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
                ->paginated([10, 25, 50, 100])
                ->defaultPaginationPageOption(25);
        });
    }
}
