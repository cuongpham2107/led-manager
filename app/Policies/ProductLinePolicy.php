<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductLine;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProductLinePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductLine');
    }

    public function view(AuthUser $authUser, ProductLine $productLine): bool
    {
        return $authUser->can('View:ProductLine');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductLine');
    }

    public function update(AuthUser $authUser, ProductLine $productLine): bool
    {
        return $authUser->can('Update:ProductLine');
    }

    public function delete(AuthUser $authUser, ProductLine $productLine): bool
    {
        return $authUser->can('Delete:ProductLine');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductLine');
    }

    public function restore(AuthUser $authUser, ProductLine $productLine): bool
    {
        return $authUser->can('Restore:ProductLine');
    }

    public function forceDelete(AuthUser $authUser, ProductLine $productLine): bool
    {
        return $authUser->can('ForceDelete:ProductLine');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductLine');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductLine');
    }

    public function replicate(AuthUser $authUser, ProductLine $productLine): bool
    {
        return $authUser->can('Replicate:ProductLine');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductLine');
    }
}
