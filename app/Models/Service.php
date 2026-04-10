<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasAttachments;
    use HasFactory;

    public const PERSON_MODE_FIXED = 'fixed';
    public const PERSON_MODE_EMPLOYEE = 'employee';
    public const PERSON_MODE_NONE = 'none';

    protected $fillable = [
        'name',
        'code',
        'standard_rate_minor',
        'uses_persons',
        'default_persons',
        'person_input_mode',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'uses_persons' => 'boolean',
    ];

    public function usesPersonsField(): bool
    {
        return $this->person_input_mode !== self::PERSON_MODE_NONE;
    }

    public function allowsEmployeePersonEntry(): bool
    {
        return $this->person_input_mode === self::PERSON_MODE_EMPLOYEE;
    }

    public function personModeLabel(): string
    {
        return match ($this->person_input_mode) {
            self::PERSON_MODE_EMPLOYEE => 'Employee selects persons',
            self::PERSON_MODE_NONE => 'Flat rate',
            default => 'Fixed '.max(1, (int) ($this->default_persons ?? 1)).' persons',
        };
    }

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
