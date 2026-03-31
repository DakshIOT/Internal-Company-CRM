<?php

namespace App\Policies;

use App\Models\DailyBillingEntry;
use App\Models\User;
use App\Support\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class DailyBillingEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole([Role::EMPLOYEE_A, Role::EMPLOYEE_B]);
    }

    public function view(User $user, DailyBillingEntry $entry): bool
    {
        return $this->ownsScopedEntry($user, $entry);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, DailyBillingEntry $entry): bool
    {
        return $this->ownsScopedEntry($user, $entry);
    }

    public function delete(User $user, DailyBillingEntry $entry): bool
    {
        return $this->ownsScopedEntry($user, $entry);
    }

    private function ownsScopedEntry(User $user, DailyBillingEntry $entry): bool
    {
        return $user->hasRole([Role::EMPLOYEE_A, Role::EMPLOYEE_B])
            && (int) $entry->user_id === (int) $user->id
            && $user->venues()->active()->whereKey($entry->venue_id)->exists();
    }
}
