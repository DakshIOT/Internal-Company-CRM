<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FunctionEntry extends Model
{
    use HasAttachments;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'venue_id',
        'entry_date',
        'name',
        'notes',
        'package_total_minor',
        'extra_charge_total_minor',
        'discount_total_minor',
        'function_total_minor',
        'paid_total_minor',
        'pending_total_minor',
        'frozen_fund_minor',
        'net_total_after_frozen_fund_minor',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(FunctionPackage::class)->orderBy('name_snapshot');
    }

    public function extraCharges(): HasMany
    {
        return $this->hasMany(FunctionExtraCharge::class)->latest('entry_date')->latest('id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(FunctionInstallment::class)->latest('entry_date')->latest('id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(FunctionDiscount::class)->latest('entry_date')->latest('id');
    }

    public function scopeForWorkspace(Builder $query, User $user, int $venueId): Builder
    {
        return $query
            ->where('user_id', $user->getKey())
            ->where('venue_id', $venueId);
    }
}
