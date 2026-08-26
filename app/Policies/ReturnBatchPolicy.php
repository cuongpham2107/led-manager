<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReturnBatch;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReturnBatchPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReturnBatch');
    }

    public function view(AuthUser $authUser, ReturnBatch $returnBatch): bool
    {
        return $authUser->can('View:ReturnBatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReturnBatch');
    }

    public function update(AuthUser $authUser, ReturnBatch $returnBatch): bool
    {
        return $authUser->can('Update:ReturnBatch');
    }

    public function delete(AuthUser $authUser, ReturnBatch $returnBatch): bool
    {
        return $authUser->can('Delete:ReturnBatch');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReturnBatch');
    }

    public function restore(AuthUser $authUser, ReturnBatch $returnBatch): bool
    {
        return $authUser->can('Restore:ReturnBatch');
    }

    public function forceDelete(AuthUser $authUser, ReturnBatch $returnBatch): bool
    {
        return $authUser->can('ForceDelete:ReturnBatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReturnBatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReturnBatch');
    }

    public function replicate(AuthUser $authUser, ReturnBatch $returnBatch): bool
    {
        return $authUser->can('Replicate:ReturnBatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReturnBatch');
    }
}
