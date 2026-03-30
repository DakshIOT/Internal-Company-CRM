<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('frozen_fund_minor')
            ->withTimestamps();
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(VenueVendor::class)->orderBy('slot_number');
    }

    public function vendorSlots(): HasMany
    {
        return $this->vendors();
    }

    public function serviceAssignments(): HasMany
    {
        return $this->hasMany(ServiceAssignment::class);
    }

    public function packageAssignments(): HasMany
    {
        return $this->hasMany(PackageAssignment::class);
    }

    public function functionEntries(): HasMany
    {
        return $this->hasMany(FunctionEntry::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function syncVendorSlots(array $names): void
    {
        foreach (range(1, 4) as $slotNumber) {
            $name = trim((string) ($names[$slotNumber] ?? 'Vendor '.$slotNumber));

            $this->vendors()->updateOrCreate(
                ['slot_number' => $slotNumber],
                ['name' => $name === '' ? 'Vendor '.$slotNumber : $name],
            );
        }
    }
}
