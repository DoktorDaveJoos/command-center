<?php

namespace App\Policies;

use App\Models\Suggestion;
use App\Models\User;

class SuggestionPolicy
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
    public function view(User $user, Suggestion $suggestion): bool
    {
        return $this->userOwnsRelatedWorkspace($user, $suggestion);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false; // Suggestions are created by the system
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Suggestion $suggestion): bool
    {
        return $this->userOwnsRelatedWorkspace($user, $suggestion);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Suggestion $suggestion): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Suggestion $suggestion): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Suggestion $suggestion): bool
    {
        return false;
    }

    /**
     * Check if user belongs to the workspace that owns this suggestion's inbox item.
     */
    private function userOwnsRelatedWorkspace(User $user, Suggestion $suggestion): bool
    {
        $inboxItem = $suggestion->inboxItem();

        if ($inboxItem === null) {
            return false;
        }

        return $user->workspaces()->where('workspaces.id', $inboxItem->workspace_id)->exists();
    }
}
