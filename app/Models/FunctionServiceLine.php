<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunctionServiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'function_package_id',
        'service_id',
        'sort_order',
        'is_selected',
        'item_name_snapshot',
        'rate_minor',
        'persons',
        'extra_charge_minor',
        'notes',
        'line_total_minor',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
    ];

    public function functionPackage(): BelongsTo
    {
        return $this->belongsTo(FunctionPackage::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
