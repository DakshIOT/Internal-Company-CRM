<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PrintSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'function_terms_and_conditions',
    ];

    public static function current(): self
    {
        if (! Schema::hasTable((new static())->getTable())) {
            return new static([
                'function_terms_and_conditions' => static::defaultFunctionTerms(),
            ]);
        }

        return static::query()->first() ?? static::query()->create([
            'function_terms_and_conditions' => static::defaultFunctionTerms(),
        ]);
    }

    public static function defaultFunctionTerms(): string
    {
        return implode("\n", [
            '1. All package selections and adjustments are treated as approved once this register is signed.',
            '2. Any extra charge, discount, or installment listed in this printout is considered part of the final daily function record.',
            '3. Attachment references are included for operational review and can be downloaded from the CRM record.',
            '4. Venue operations should verify counts, amounts, and notes before customer and manager signatures are collected.',
        ]);
    }
}
