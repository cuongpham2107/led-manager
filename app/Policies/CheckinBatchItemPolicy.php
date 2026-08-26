<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CheckinBatchItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CheckinBatchItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CheckinBatchItem');
    }

    public function view(AuthUser $authUser, CheckinBatchItem $checkinBatchItem): bool
    {
        return $authUser->can('View:CheckinBatchItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CheckinBatchItem');
    }

    public function update(AuthUser $authUser, CheckinBatchItem $checkinBatchItem): bool
    {
        return $authUser->can('Update:CheckinBatchItem');
    }

    public function delete(AuthUser $authUser, CheckinBatchItem $checkinBatchItem): bool
    {
        return $authUser->can('Delete:CheckinBatchItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CheckinBatchItem');
    }

    public function restore(AuthUser $authUser, CheckinBatchItem $checkinBatchItem): bool
    {
        return $authUser->can('Restore:CheckinBatchItem');
    }

    public function forceDelete(AuthUser $authUser, CheckinBatchItem $checkinBatchItem): bool
    {
        return $authUser->can('ForceDelete:CheckinBatchItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CheckinBatchItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CheckinBatchItem');
    }

    public function replicate(AuthUser $authUser, CheckinBatchItem $checkinBatchItem): bool
    {
        return $authUser->can('Replicate:CheckinBatchItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CheckinBatchItem');
    }
}
