<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CheckinBatch;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CheckinBatchPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CheckinBatch');
    }

    public function view(AuthUser $authUser, CheckinBatch $checkinBatch): bool
    {
        return $authUser->can('View:CheckinBatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CheckinBatch');
    }

    public function update(AuthUser $authUser, CheckinBatch $checkinBatch): bool
    {
        return $authUser->can('Update:CheckinBatch');
    }

    public function delete(AuthUser $authUser, CheckinBatch $checkinBatch): bool
    {
        return $authUser->can('Delete:CheckinBatch');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CheckinBatch');
    }

    public function restore(AuthUser $authUser, CheckinBatch $checkinBatch): bool
    {
        return $authUser->can('Restore:CheckinBatch');
    }

    public function forceDelete(AuthUser $authUser, CheckinBatch $checkinBatch): bool
    {
        return $authUser->can('ForceDelete:CheckinBatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CheckinBatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CheckinBatch');
    }

    public function replicate(AuthUser $authUser, CheckinBatch $checkinBatch): bool
    {
        return $authUser->can('Replicate:CheckinBatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CheckinBatch');
    }
}
