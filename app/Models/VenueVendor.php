<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class VenueVendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'slot_number',
        'name',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function vendorEntries(): HasMany
    {
        return $this->hasMany(VendorEntry::class);
    }
}
