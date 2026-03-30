<?php

namespace App\Support;

class TransactionMode
{
    public const CASH = 'cash';
    public const UPI = 'upi';
    public const BANK = 'bank';
    public const CARD = 'card';
    public const OTHER = 'other';
    public const SWEETS = 'sweets';

    public static function all(): array
    {
        return [
            self::CASH,
            self::UPI,
            self::BANK,
            self::CARD,
            self::OTHER,
            self::SWEETS,
        ];
    }

    public static function options(): array
    {
        return collect(self::all())
            ->mapWithKeys(fn (string $mode) => [$mode => strtoupper($mode)])
            ->all();
    }
}
