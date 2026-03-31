<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToEmployeeVenue
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function scopeForWorkspace(Builder $query, User $user, int $venueId): Builder
    {
        $table = $query->getModel()->getTable();

        return $query
            ->where($table.'.user_id', $user->getKey())
            ->where($table.'.venue_id', $venueId);
    }
}
