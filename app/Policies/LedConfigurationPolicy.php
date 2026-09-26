<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LedConfiguration;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LedConfigurationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LedConfiguration');
    }

    public function view(AuthUser $authUser, LedConfiguration $ledConfiguration): bool
    {
        return $authUser->can('View:LedConfiguration');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LedConfiguration');
    }

    public function update(AuthUser $authUser, LedConfiguration $ledConfiguration): bool
    {
        return $authUser->can('Update:LedConfiguration');
    }

    public function delete(AuthUser $authUser, LedConfiguration $ledConfiguration): bool
    {
        return $authUser->can('Delete:LedConfiguration');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LedConfiguration');
    }

    public function restore(AuthUser $authUser, LedConfiguration $ledConfiguration): bool
    {
        return $authUser->can('Restore:LedConfiguration');
    }

    public function forceDelete(AuthUser $authUser, LedConfiguration $ledConfiguration): bool
    {
        return $authUser->can('ForceDelete:LedConfiguration');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LedConfiguration');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LedConfiguration');
    }

    public function replicate(AuthUser $authUser, LedConfiguration $ledConfiguration): bool
    {
        return $authUser->can('Replicate:LedConfiguration');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LedConfiguration');
    }
}
