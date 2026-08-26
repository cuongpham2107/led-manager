<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CheckoutBatch;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CheckoutBatchPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CheckoutBatch');
    }

    public function view(AuthUser $authUser, CheckoutBatch $checkoutBatch): bool
    {
        return $authUser->can('View:CheckoutBatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CheckoutBatch');
    }

    public function update(AuthUser $authUser, CheckoutBatch $checkoutBatch): bool
    {
        return $authUser->can('Update:CheckoutBatch');
    }

    public function delete(AuthUser $authUser, CheckoutBatch $checkoutBatch): bool
    {
        return $authUser->can('Delete:CheckoutBatch');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CheckoutBatch');
    }

    public function restore(AuthUser $authUser, CheckoutBatch $checkoutBatch): bool
    {
        return $authUser->can('Restore:CheckoutBatch');
    }

    public function forceDelete(AuthUser $authUser, CheckoutBatch $checkoutBatch): bool
    {
        return $authUser->can('ForceDelete:CheckoutBatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CheckoutBatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CheckoutBatch');
    }

    public function replicate(AuthUser $authUser, CheckoutBatch $checkoutBatch): bool
    {
        return $authUser->can('Replicate:CheckoutBatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CheckoutBatch');
    }
}
