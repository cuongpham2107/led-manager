<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RepairLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RepairLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RepairLog');
    }

    public function view(AuthUser $authUser, RepairLog $repairLog): bool
    {
        return $authUser->can('View:RepairLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RepairLog');
    }

    public function update(AuthUser $authUser, RepairLog $repairLog): bool
    {
        return $authUser->can('Update:RepairLog');
    }

    public function delete(AuthUser $authUser, RepairLog $repairLog): bool
    {
        return $authUser->can('Delete:RepairLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RepairLog');
    }

    public function restore(AuthUser $authUser, RepairLog $repairLog): bool
    {
        return $authUser->can('Restore:RepairLog');
    }

    public function forceDelete(AuthUser $authUser, RepairLog $repairLog): bool
    {
        return $authUser->can('ForceDelete:RepairLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RepairLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RepairLog');
    }

    public function replicate(AuthUser $authUser, RepairLog $repairLog): bool
    {
        return $authUser->can('Replicate:RepairLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RepairLog');
    }
}
