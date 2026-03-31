<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmployeeVenue;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEntry extends Model
{
    use BelongsToEmployeeVenue;
    use HasAttachments;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'venue_id',
        'venue_vendor_id',
        'vendor_name_snapshot',
        'entry_date',
        'name',
        'amount_minor',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function venueVendor(): BelongsTo
    {
        return $this->belongsTo(VenueVendor::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->venueVendor();
    }
}
