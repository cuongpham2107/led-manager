<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\Agency;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    public static bool $formActionsAreSticky = true;

    public static string|Alignment $formActionsAlignment = Alignment::End;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user?->isAgencyScoped()) {
            $data['agency_id'] = $user->getScopedAgencyId();
            if (empty($data['warehouse_id']) && ($agency = $user->agency)) {
                $data['warehouse_id'] = $agency->warehouse_id;
            }
            if (empty($data['sales_user_id'])) {
                $data['sales_user_id'] = $user->id;
            }
        }

        // Snapshot commission_rate from agency if not already populated
        if (! empty($data['agency_id']) && empty($data['commission_rate'])) {
            $data['commission_rate'] = Agency::find($data['agency_id'])?->commission_rate;
        }

        OrderForm::validateAvailability($data);

        return $data;
    }
}
