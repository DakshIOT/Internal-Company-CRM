<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\UpdateEmployeeAssignmentsRequest;
use App\Models\Package;
use App\Models\PackageAssignment;
use App\Models\Service;
use App\Models\ServiceAssignment;
use App\Models\User;
use App\Models\Venue;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeAssignmentController extends Controller
{
    public function edit(User $employee): View
    {
        abort_if($employee->isAdmin(), 404);

        $employee->load(['venues', 'serviceAssignments', 'packageAssignments']);
        $packages = Package::query()->with('services')->orderBy('name')->get();

        return view('admin.master-data.employees.assignments', [
            'employee' => $employee,
            'venues' => Venue::query()->orderBy('name')->get(),
            'services' => Service::query()->orderBy('name')->get(),
            'packages' => $packages,
            'assignedVenueIds' => $employee->venues->pluck('id')->all(),
            'frozenFunds' => $employee->venues
                ->mapWithKeys(fn (Venue $venue) => [$venue->id => Money::formatMinor($venue->pivot->frozen_fund_minor)])
                ->all(),
            'serviceIdsByVenue' => $employee->serviceAssignments
                ->groupBy('venue_id')
                ->map(fn ($assignments) => $assignments->pluck('service_id')->values()->all())
                ->all(),
            'packageIdsByVenue' => $employee->packageAssignments
                ->groupBy('venue_id')
                ->map(fn ($assignments) => $assignments->pluck('package_id')->values()->all())
                ->all(),
            'packageServiceIds' => $packages
                ->mapWithKeys(fn (Package $package) => [$package->id => $package->services->pluck('id')->all()])
                ->all(),
        ]);
    }

    public function update(UpdateEmployeeAssignmentsRequest $request, User $employee): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);

        $venueIds = collect($request->validated('venue_ids', []))
            ->map(fn ($venueId) => (int) $venueId)
            ->unique()
            ->values();

        $frozenFunds = collect($request->validated('frozen_funds', []));
        $serviceIdsByVenue = collect($request->validated('service_ids_by_venue', []));
        $packageIdsByVenue = collect($request->validated('package_ids_by_venue', []));

        DB::transaction(function () use ($employee, $venueIds, $frozenFunds, $serviceIdsByVenue, $packageIdsByVenue) {
            $venueSyncData = $venueIds->mapWithKeys(function (int $venueId) use ($employee, $frozenFunds) {
                $frozenFundMinor = $employee->supportsFrozenFund()
                    ? Money::toMinor($frozenFunds->get($venueId))
                    : 0;

                return [$venueId => ['frozen_fund_minor' => $frozenFundMinor]];
            })->all();

            $employee->venues()->sync($venueSyncData);

            ServiceAssignment::query()->where('user_id', $employee->id)->delete();
            PackageAssignment::query()->where('user_id', $employee->id)->delete();

            foreach ($venueIds as $venueId) {
                $selectedPackageIds = collect($packageIdsByVenue->get((string) $venueId, []))
                    ->map(fn ($packageId) => (int) $packageId)
                    ->unique()
                    ->values();

                $derivedServiceIds = Package::query()
                    ->whereIn('id', $selectedPackageIds)
                    ->with('services:id')
                    ->get()
                    ->flatMap(fn (Package $package) => $package->services->pluck('id'))
                    ->map(fn ($serviceId) => (int) $serviceId)
                    ->unique()
                    ->values();

                $manualServiceIds = collect($serviceIdsByVenue->get((string) $venueId, []))
                    ->map(fn ($serviceId) => (int) $serviceId)
                    ->unique()
                    ->values();

                $serviceRows = $derivedServiceIds
                    ->merge($manualServiceIds)
                    ->unique()
                    ->map(fn ($serviceId) => [
                        'user_id' => $employee->id,
                        'venue_id' => $venueId,
                        'service_id' => (int) $serviceId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                    ->unique(fn (array $row) => $row['service_id'])
                    ->values()
                    ->all();

                if ($serviceRows !== []) {
                    ServiceAssignment::insert($serviceRows);
                }

                $packageRows = $selectedPackageIds
                    ->map(fn ($packageId) => [
                        'user_id' => $employee->id,
                        'venue_id' => $venueId,
                        'package_id' => (int) $packageId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                    ->unique(fn (array $row) => $row['package_id'])
                    ->values()
                    ->all();

                if ($packageRows !== []) {
                    PackageAssignment::insert($packageRows);
                }
            }
        });

        return redirect()
            ->route('admin.master-data.employees.assignments.edit', $employee)
            ->with('status', 'Assignments updated successfully.');
    }
}
