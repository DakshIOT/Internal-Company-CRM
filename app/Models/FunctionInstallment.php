<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunctionInstallment extends Model
{
    use HasAttachments;
    use HasFactory;

    protected $fillable = [
        'function_entry_id',
        'entry_date',
        'name',
        'mode',
        'amount_minor',
        'note',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function functionEntry(): BelongsTo
    {
        return $this->belongsTo(FunctionEntry::class);
    }
}
