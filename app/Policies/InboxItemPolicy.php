<?php

namespace App\Policies;

use App\Models\InboxItem;
use App\Models\User;

class InboxItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentWorkspace() !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InboxItem $inboxItem): bool
    {
        return $user->workspaces()->where('workspaces.id', $inboxItem->workspace_id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->currentWorkspace() !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InboxItem $inboxItem): bool
    {
        return $user->workspaces()->where('workspaces.id', $inboxItem->workspace_id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InboxItem $inboxItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InboxItem $inboxItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InboxItem $inboxItem): bool
    {
        return false;
    }
}
