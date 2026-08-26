<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AssetStatusLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AssetStatusLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AssetStatusLog');
    }

    public function view(AuthUser $authUser, AssetStatusLog $assetStatusLog): bool
    {
        return $authUser->can('View:AssetStatusLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AssetStatusLog');
    }

    public function update(AuthUser $authUser, AssetStatusLog $assetStatusLog): bool
    {
        return $authUser->can('Update:AssetStatusLog');
    }

    public function delete(AuthUser $authUser, AssetStatusLog $assetStatusLog): bool
    {
        return $authUser->can('Delete:AssetStatusLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AssetStatusLog');
    }

    public function restore(AuthUser $authUser, AssetStatusLog $assetStatusLog): bool
    {
        return $authUser->can('Restore:AssetStatusLog');
    }

    public function forceDelete(AuthUser $authUser, AssetStatusLog $assetStatusLog): bool
    {
        return $authUser->can('ForceDelete:AssetStatusLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AssetStatusLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AssetStatusLog');
    }

    public function replicate(AuthUser $authUser, AssetStatusLog $assetStatusLog): bool
    {
        return $authUser->can('Replicate:AssetStatusLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AssetStatusLog');
    }
}
