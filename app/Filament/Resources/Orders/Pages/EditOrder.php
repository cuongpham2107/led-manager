<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\Actions\AssignCrewAction;
use App\Filament\Resources\Orders\Actions\ChangeOrderAction;
use App\Filament\Resources\Orders\Actions\CompleteOrderAction;
use App\Filament\Resources\Orders\Actions\CreateCheckoutBatchAction;
use App\Filament\Resources\Orders\Actions\CreateContractAction;
use App\Filament\Resources\Orders\Actions\DispatchOrderAction;
use App\Filament\Resources\Orders\Actions\ManageTimelineAction;
use App\Filament\Resources\Orders\Actions\ReturnOrderAction;
use App\Filament\Resources\Orders\Actions\ViewCheckoutBatchAction;
use App\Filament\Resources\Orders\Actions\ViewContractAction;
use App\Filament\Resources\Orders\Actions\ViewReturnBatchAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\Agency;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public static bool $formActionsAreSticky = true;

    public static string|Alignment $formActionsAlignment = Alignment::End;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateContractAction::make(),
            ViewContractAction::make(),
            CreateCheckoutBatchAction::make(),
            ViewCheckoutBatchAction::make(),
            DispatchOrderAction::make(),
            ReturnOrderAction::make(),
            ViewReturnBatchAction::make(),
            ChangeOrderAction::make(),
            AssignCrewAction::make(),
            ManageTimelineAction::make(),
            CompleteOrderAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user?->isAgencyScoped()) {
            $data['agency_id'] = $user->getScopedAgencyId();
            if (empty($data['warehouse_id']) && ($agency = $user->agency)) {
                $data['warehouse_id'] = $agency->warehouse_id;
            }
        }

        // If order belongs to an agency but lacks commission snapshot (e.g. legacy order), snapshot it
        if (! empty($data['agency_id']) && empty($data['commission_rate'])) {
            $data['commission_rate'] = Agency::find($data['agency_id'])?->commission_rate;
        }

        OrderForm::validateAvailability($data, $this->getRecord()->id);

        return $data;
    }
}
