<?php

namespace App\Policies;

use App\Models\FunctionEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FunctionEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isEmployee();
    }

    public function view(User $user, FunctionEntry $functionEntry): bool
    {
        return $this->ownsEntryInSelectedVenue($user, $functionEntry);
    }

    public function create(User $user): bool
    {
        return $user->isEmployee();
    }

    public function update(User $user, FunctionEntry $functionEntry): bool
    {
        return $this->ownsEntryInSelectedVenue($user, $functionEntry);
    }

    public function delete(User $user, FunctionEntry $functionEntry): bool
    {
        return $this->ownsEntryInSelectedVenue($user, $functionEntry);
    }

    private function ownsEntryInSelectedVenue(User $user, FunctionEntry $functionEntry): bool
    {
        return $user->isEmployee()
            && (int) $functionEntry->user_id === (int) $user->id
            && $user->venues()->active()->whereKey($functionEntry->venue_id)->exists();
    }
}
