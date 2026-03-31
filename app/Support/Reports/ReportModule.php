<?php

namespace App\Support\Reports;

class ReportModule
{
    public const FUNCTIONS = 'functions';
    public const DAILY_INCOME = 'daily_income';
    public const DAILY_BILLING = 'daily_billing';
    public const VENDOR_ENTRIES = 'vendor_entries';
    public const ADMIN_INCOME = 'admin_income';

    public static function all(): array
    {
        return [
            self::FUNCTIONS,
            self::DAILY_INCOME,
            self::DAILY_BILLING,
            self::VENDOR_ENTRIES,
            self::ADMIN_INCOME,
        ];
    }

    public static function label(string $module): string
    {
        return match ($module) {
            self::FUNCTIONS => 'Function Entry',
            self::DAILY_INCOME => 'Daily Income',
            self::DAILY_BILLING => 'Daily Billing',
            self::VENDOR_ENTRIES => 'Vendor Entry',
            self::ADMIN_INCOME => 'Admin Income',
            default => 'Unknown Module',
        };
    }

    public static function options(): array
    {
        return collect(self::all())
            ->mapWithKeys(fn (string $module) => [$module => self::label($module)])
            ->all();
    }

    public static function routeName(string $module): string
    {
        return match ($module) {
            self::FUNCTIONS => 'admin.reports.functions.index',
            self::DAILY_INCOME => 'admin.reports.daily-income.index',
            self::DAILY_BILLING => 'admin.reports.daily-billing.index',
            self::VENDOR_ENTRIES => 'admin.reports.vendor-entries.index',
            self::ADMIN_INCOME => 'admin.reports.admin-income.index',
            default => 'admin.reports.index',
        };
    }

    public static function exportRouteName(string $module): string
    {
        return match ($module) {
            self::FUNCTIONS => 'admin.reports.functions.export',
            self::DAILY_INCOME => 'admin.reports.daily-income.export',
            self::DAILY_BILLING => 'admin.reports.daily-billing.export',
            self::VENDOR_ENTRIES => 'admin.reports.vendor-entries.export',
            self::ADMIN_INCOME => 'admin.reports.admin-income.export',
            default => 'admin.reports.index',
        };
    }

    public static function filenamePrefix(string $module): string
    {
        return str_replace('_', '-', $module).'-report';
    }
}
