<?php

namespace App\Support;

class Role
{
    public const ADMIN = 'admin';
    public const EMPLOYEE_A = 'employee_a';
    public const EMPLOYEE_B = 'employee_b';
    public const EMPLOYEE_C = 'employee_c';

    public static function all(): array
    {
        return [
            self::ADMIN,
            self::EMPLOYEE_A,
            self::EMPLOYEE_B,
            self::EMPLOYEE_C,
        ];
    }

    public static function employeeRoles(): array
    {
        return [
            self::EMPLOYEE_A,
            self::EMPLOYEE_B,
            self::EMPLOYEE_C,
        ];
    }

    public static function label(string $role): string
    {
        return match ($role) {
            self::ADMIN => 'Admin',
            self::EMPLOYEE_A => 'Employee Type A',
            self::EMPLOYEE_B => 'Employee Type B',
            self::EMPLOYEE_C => 'Employee Type C',
            default => 'Unknown Role',
        };
    }

    public static function options(): array
    {
        return collect(self::all())
            ->mapWithKeys(fn (string $role) => [$role => self::label($role)])
            ->all();
    }

    public static function dashboardHeadline(string $role): string
    {
        return match ($role) {
            self::ADMIN => 'Global company overview',
            self::EMPLOYEE_A => 'Function, income, and billing workspace',
            self::EMPLOYEE_B => 'Function, billing, income, and vendor workspace',
            self::EMPLOYEE_C => 'Function-first workspace',
            default => 'Dashboard',
        };
    }

    public static function modulesFor(string $role): array
    {
        return match ($role) {
            self::ADMIN => ['Global dashboard', 'Employee management', 'Venue management', 'Admin Income', 'Reporting', 'Exports'],
            self::EMPLOYEE_A => ['Function Entry', 'Daily Income', 'Daily Billing'],
            self::EMPLOYEE_B => ['Function Entry', 'Daily Income', 'Daily Billing', 'Vendor Entry'],
            self::EMPLOYEE_C => ['Function Entry'],
            default => [],
        };
    }

    public static function supportsFrozenFund(string $role): bool
    {
        return $role === self::EMPLOYEE_A;
    }
}
