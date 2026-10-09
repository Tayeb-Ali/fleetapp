<?php

declare(strict_types=1);

namespace App\Domain\Identity\Policies;

use App\Models\User;

/**
 * Only admins may manage users. Mirrors legacy server.js behaviour where
 * every /api/users endpoint returned 403 for non-admins, and an admin
 * cannot disable their own account or change their own role (line 182).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }

    public function resetPassword(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /** Toggling active status; self-disable is forbidden (legacy line 182). */
    public function toggleActive(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }

    /** Changing role; an admin cannot change their own role (legacy line 182). */
    public function changeRole(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }
}
