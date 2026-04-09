<?php

namespace App\Models;

use App\Support\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class)
            ->withPivot('frozen_fund_minor')
            ->withTimestamps();
    }

    public function serviceAssignments(): HasMany
    {
        return $this->hasMany(ServiceAssignment::class);
    }

    public function packageAssignments(): HasMany
    {
        return $this->hasMany(PackageAssignment::class);
    }

    public function packageServiceAssignments(): HasMany
    {
        return $this->hasMany(PackageServiceAssignment::class);
    }

    public function functionEntries(): HasMany
    {
        return $this->hasMany(FunctionEntry::class);
    }

    public function dailyIncomeEntries(): HasMany
    {
        return $this->hasMany(DailyIncomeEntry::class);
    }

    public function dailyBillingEntries(): HasMany
    {
        return $this->hasMany(DailyBillingEntry::class);
    }

    public function vendorEntries(): HasMany
    {
        return $this->hasMany(VendorEntry::class);
    }

    public function adminIncomeEntries(): HasMany
    {
        return $this->hasMany(AdminIncomeEntry::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    public function isEmployee(): bool
    {
        return in_array($this->role, Role::employeeRoles(), true);
    }

    public function hasRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($this->role, $roles, true);
    }

    public function roleLabel(): string
    {
        return Role::label($this->role);
    }

    public function supportsFrozenFund(): bool
    {
        return Role::supportsFrozenFund($this->role);
    }

    public function frozenFundMinorForVenue(int $venueId): int
    {
        $pivot = $this->venues
            ->firstWhere('id', $venueId)?->pivot;

        return (int) ($pivot?->frozen_fund_minor ?? 0);
    }
}
