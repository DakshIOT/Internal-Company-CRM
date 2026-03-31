<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmployeeVenue;
use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyIncomeEntry extends Model
{
    use BelongsToEmployeeVenue;
    use HasAttachments;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'venue_id',
        'entry_date',
        'name',
        'amount_minor',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];
}
