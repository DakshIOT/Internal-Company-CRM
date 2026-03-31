<?php

namespace App\Policies;

use App\Models\AdminIncomeEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminIncomeEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AdminIncomeEntry $entry): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AdminIncomeEntry $entry): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, AdminIncomeEntry $entry): bool
    {
        return $user->isAdmin();
    }
}
