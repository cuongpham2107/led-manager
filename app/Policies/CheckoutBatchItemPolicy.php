<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CheckoutBatchItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CheckoutBatchItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CheckoutBatchItem');
    }

    public function view(AuthUser $authUser, CheckoutBatchItem $checkoutBatchItem): bool
    {
        return $authUser->can('View:CheckoutBatchItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CheckoutBatchItem');
    }

    public function update(AuthUser $authUser, CheckoutBatchItem $checkoutBatchItem): bool
    {
        return $authUser->can('Update:CheckoutBatchItem');
    }

    public function delete(AuthUser $authUser, CheckoutBatchItem $checkoutBatchItem): bool
    {
        return $authUser->can('Delete:CheckoutBatchItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CheckoutBatchItem');
    }

    public function restore(AuthUser $authUser, CheckoutBatchItem $checkoutBatchItem): bool
    {
        return $authUser->can('Restore:CheckoutBatchItem');
    }

    public function forceDelete(AuthUser $authUser, CheckoutBatchItem $checkoutBatchItem): bool
    {
        return $authUser->can('ForceDelete:CheckoutBatchItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CheckoutBatchItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CheckoutBatchItem');
    }

    public function replicate(AuthUser $authUser, CheckoutBatchItem $checkoutBatchItem): bool
    {
        return $authUser->can('Replicate:CheckoutBatchItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CheckoutBatchItem');
    }
}
