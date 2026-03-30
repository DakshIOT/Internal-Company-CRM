<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uploaded_by',
        'disk',
        'storage_path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function canPreviewInline(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/')
            || $this->mime_type === 'application/pdf';
    }
}
