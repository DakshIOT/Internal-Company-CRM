<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorEntry;
use App\Support\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::EMPLOYEE_B);
    }

    public function view(User $user, VendorEntry $entry): bool
    {
        return $this->ownsScopedEntry($user, $entry);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, VendorEntry $entry): bool
    {
        return $this->ownsScopedEntry($user, $entry);
    }

    public function delete(User $user, VendorEntry $entry): bool
    {
        return $this->ownsScopedEntry($user, $entry);
    }

    private function ownsScopedEntry(User $user, VendorEntry $entry): bool
    {
        return $user->hasRole(Role::EMPLOYEE_B)
            && (int) $entry->user_id === (int) $user->id
            && $user->venues()->active()->whereKey($entry->venue_id)->exists();
    }
}
