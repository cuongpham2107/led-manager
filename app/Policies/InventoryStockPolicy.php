<?php

namespace App\Policies;

use App\Models\InventoryStock;
use App\Models\User;

class InventoryStockPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->can('ViewAny:Asset');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InventoryStock $inventoryStock): bool
    {
        return $user->hasRole(['super_admin', 'admin']) || $user->can('View:Asset');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InventoryStock $inventoryStock): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventoryStock $inventoryStock): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InventoryStock $inventoryStock): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InventoryStock $inventoryStock): bool
    {
        return false;
    }
}
