<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'package_service')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('package_service.sort_order');
    }

    public function serviceCount(): int
    {
        return $this->services->count();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PackageAssignment::class);
    }

    public function serviceAssignments(): HasMany
    {
        return $this->hasMany(PackageServiceAssignment::class);
    }

    public function functionPackages(): HasMany
    {
        return $this->hasMany(FunctionPackage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
