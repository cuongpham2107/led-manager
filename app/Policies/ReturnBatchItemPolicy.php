<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReturnBatchItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReturnBatchItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReturnBatchItem');
    }

    public function view(AuthUser $authUser, ReturnBatchItem $returnBatchItem): bool
    {
        return $authUser->can('View:ReturnBatchItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReturnBatchItem');
    }

    public function update(AuthUser $authUser, ReturnBatchItem $returnBatchItem): bool
    {
        return $authUser->can('Update:ReturnBatchItem');
    }

    public function delete(AuthUser $authUser, ReturnBatchItem $returnBatchItem): bool
    {
        return $authUser->can('Delete:ReturnBatchItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReturnBatchItem');
    }

    public function restore(AuthUser $authUser, ReturnBatchItem $returnBatchItem): bool
    {
        return $authUser->can('Restore:ReturnBatchItem');
    }

    public function forceDelete(AuthUser $authUser, ReturnBatchItem $returnBatchItem): bool
    {
        return $authUser->can('ForceDelete:ReturnBatchItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReturnBatchItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReturnBatchItem');
    }

    public function replicate(AuthUser $authUser, ReturnBatchItem $returnBatchItem): bool
    {
        return $authUser->can('Replicate:ReturnBatchItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReturnBatchItem');
    }
}
