<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunctionServiceLine extends Model
{
    use HasFactory;

    public const PERSON_MODE_FIXED = 'fixed';
    public const PERSON_MODE_EMPLOYEE = 'employee';
    public const PERSON_MODE_NONE = 'none';

    protected $fillable = [
        'function_package_id',
        'service_id',
        'sort_order',
        'is_selected',
        'item_name_snapshot',
        'rate_minor',
        'uses_persons',
        'person_input_mode',
        'persons',
        'extra_charge_minor',
        'notes',
        'line_total_minor',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
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

    public function functionPackage(): BelongsTo
    {
        return $this->belongsTo(FunctionPackage::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
