<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QuotationItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class QuotationItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QuotationItem');
    }

    public function view(AuthUser $authUser, QuotationItem $quotationItem): bool
    {
        return $authUser->can('View:QuotationItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QuotationItem');
    }

    public function update(AuthUser $authUser, QuotationItem $quotationItem): bool
    {
        return $authUser->can('Update:QuotationItem');
    }

    public function delete(AuthUser $authUser, QuotationItem $quotationItem): bool
    {
        return $authUser->can('Delete:QuotationItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:QuotationItem');
    }

    public function restore(AuthUser $authUser, QuotationItem $quotationItem): bool
    {
        return $authUser->can('Restore:QuotationItem');
    }

    public function forceDelete(AuthUser $authUser, QuotationItem $quotationItem): bool
    {
        return $authUser->can('ForceDelete:QuotationItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QuotationItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QuotationItem');
    }

    public function replicate(AuthUser $authUser, QuotationItem $quotationItem): bool
    {
        return $authUser->can('Replicate:QuotationItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QuotationItem');
    }
}
