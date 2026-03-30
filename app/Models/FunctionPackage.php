<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FunctionPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'function_entry_id',
        'package_id',
        'name_snapshot',
        'code_snapshot',
        'total_minor',
    ];

    public function functionEntry(): BelongsTo
    {
        return $this->belongsTo(FunctionEntry::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function serviceLines(): HasMany
    {
        return $this->hasMany(FunctionServiceLine::class)->orderBy('sort_order');
    }
}
