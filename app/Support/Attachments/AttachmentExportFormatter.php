<?php

namespace App\Support\Attachments;

use Illuminate\Support\Collection;

class AttachmentExportFormatter
{
    public static function names(iterable $attachments): string
    {
        return static::collection($attachments)
            ->pluck('original_name')
            ->filter()
            ->implode("\n");
    }

    public static function urls(iterable $attachments, callable $resolver): string
    {
        return static::collection($attachments)
            ->map(fn ($attachment) => $resolver($attachment))
            ->filter()
            ->implode("\n");
    }

    protected static function collection(iterable $attachments): Collection
    {
        return $attachments instanceof Collection
            ? $attachments
            : collect($attachments);
    }
}
