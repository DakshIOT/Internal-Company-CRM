<?php

namespace App\Reports\Filters;

class ReportFilters
{
    public ?string $dateFrom;
    public ?string $dateTo;
    public ?int $venueId;
    public ?int $userId;
    public ?string $employeeRole;
    public ?string $module;
    public ?int $vendorId;
    public ?int $serviceId;
    public ?int $packageId;
    public ?string $search;

    public function __construct(array $attributes = [])
    {
        $this->dateFrom = $attributes['date_from'] ?? null;
        $this->dateTo = $attributes['date_to'] ?? null;
        $this->venueId = isset($attributes['venue_id']) ? (int) $attributes['venue_id'] : null;
        $this->userId = isset($attributes['user_id']) ? (int) $attributes['user_id'] : null;
        $this->employeeRole = $attributes['employee_role'] ?? null;
        $this->module = $attributes['module'] ?? null;
        $this->vendorId = isset($attributes['vendor_id']) ? (int) $attributes['vendor_id'] : null;
        $this->serviceId = isset($attributes['service_id']) ? (int) $attributes['service_id'] : null;
        $this->packageId = isset($attributes['package_id']) ? (int) $attributes['package_id'] : null;
        $this->search = $attributes['search'] ?? null;
    }

    public static function fromArray(array $attributes): self
    {
        return new self(array_filter($attributes, static function ($value) {
            return ! is_null($value) && $value !== '';
        }));
    }

    public function toArray(): array
    {
        return [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'venue_id' => $this->venueId,
            'user_id' => $this->userId,
            'employee_role' => $this->employeeRole,
            'module' => $this->module,
            'vendor_id' => $this->vendorId,
            'service_id' => $this->serviceId,
            'package_id' => $this->packageId,
            'search' => $this->search,
        ];
    }

    public function query(): array
    {
        return array_filter($this->toArray(), static function ($value) {
            return ! is_null($value) && $value !== '';
        });
    }

    public function hasActiveFilters(): bool
    {
        return $this->query() !== [];
    }
}
