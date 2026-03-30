<?php

namespace App\Support;

class Money
{
    public static function toMinor(null|int|float|string $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $normalized = preg_replace('/[^\d.]/', '', (string) $value) ?? '0';

        if ($normalized === '' || $normalized === '.') {
            return 0;
        }

        return (int) round(((float) $normalized) * 100);
    }

    public static function formatMinor(?int $value): string
    {
        return number_format(((int) $value) / 100, 2, '.', ',');
    }
}
