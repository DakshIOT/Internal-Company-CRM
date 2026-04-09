<?php

namespace App\Services\Reports;

use App\Models\Package;
use App\Models\PackageAssignment;
use App\Models\Service;
use App\Models\ServiceAssignment;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueVendor;
use App\Reports\Filters\ReportFilters;
use App\Support\Reports\ReportModule;
use App\Support\Role;

class ReportFilterOptionsService
{
    public function forFilters(ReportFilters $filters): array
    {
        $employees = User::query()
            ->whereIn('role', Role::employeeRoles())
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $selectedEmployee = $employees->firstWhere('id', $filters->userId);

        $venues = $selectedEmployee
            ? $selectedEmployee->venues()->orderBy('name')->get(['venues.id', 'venues.name'])
            : Venue::query()->whereRaw('1 = 0')->get(['id', 'name']);

        $vendors = VenueVendor::query()
            ->with('venue:id,name')
            ->when($selectedEmployee, function ($query) use ($selectedEmployee, $filters) {
                $query->whereIn('venue_id', $selectedEmployee->venues->pluck('id'))
                    ->when($filters->venueId, fn ($venueQuery) => $venueQuery->where('venue_id', $filters->venueId));
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($selectedEmployee && $selectedEmployee->role !== Role::EMPLOYEE_B, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('name')
            ->get();

        $packages = Package::query()
            ->when($selectedEmployee, function ($query) use ($selectedEmployee, $filters) {
                $query->whereIn('id', PackageAssignment::query()
                    ->select('package_id')
                    ->where('user_id', $selectedEmployee->id)
                    ->when($filters->venueId, fn ($assignmentQuery) => $assignmentQuery->where('venue_id', $filters->venueId))
                );
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $services = Service::query()
            ->when($selectedEmployee, function ($query) use ($selectedEmployee, $filters) {
                $query->whereIn('id', ServiceAssignment::query()
                    ->select('service_id')
                    ->where('user_id', $selectedEmployee->id)
                    ->when($filters->venueId, fn ($assignmentQuery) => $assignmentQuery->where('venue_id', $filters->venueId))
                );
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'venues' => $venues,
            'users' => $employees,
            'employee_roles' => Role::options(),
            'modules' => collect(ReportModule::employeeScoped())
                ->mapWithKeys(fn (string $module) => [$module => ReportModule::label($module)])
                ->all(),
            'packages' => $packages,
            'services' => $services,
            'vendors' => $vendors,
        ];
    }
}
