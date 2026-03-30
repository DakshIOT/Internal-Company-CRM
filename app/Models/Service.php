<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'standard_rate_minor',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_service')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('package_service.sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ServiceAssignment::class);
    }

    public function functionServiceLines(): HasMany
    {
        return $this->hasMany(FunctionServiceLine::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
