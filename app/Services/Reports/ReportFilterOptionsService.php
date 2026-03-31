<?php

namespace App\Services\Reports;

use App\Models\Package;
use App\Models\Service;
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
        $vendors = VenueVendor::query()
            ->with('venue:id,name')
            ->when($filters->venueId, fn ($query) => $query->where('venue_id', $filters->venueId))
            ->orderBy('name')
            ->get();

        return [
            'venues' => Venue::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'role']),
            'employee_roles' => Role::options(),
            'modules' => ReportModule::options(),
            'packages' => Package::query()->orderBy('name')->get(['id', 'name']),
            'services' => Service::query()->orderBy('name')->get(['id', 'name']),
            'vendors' => $vendors,
        ];
    }
}
