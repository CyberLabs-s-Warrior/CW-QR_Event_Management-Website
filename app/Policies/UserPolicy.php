<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function restore(User $user, User $model): bool
    {
        return $user->role === 'super_admin';
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->role === 'super_admin';
    }

    public function isSuperOrAdmin(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin']) || $user->id === 1;
    }
}
