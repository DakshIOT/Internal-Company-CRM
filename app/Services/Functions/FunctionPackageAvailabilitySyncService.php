<?php

namespace App\Services\Functions;

use App\Models\FunctionEntry;
use App\Models\FunctionPackage;
use App\Models\PackageServiceAssignment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;

class FunctionPackageAvailabilitySyncService
{
    public function syncEntry(FunctionEntry $functionEntry, User $user): void
    {
        $functionEntry->loadMissing('packages.package');

        $functionEntry->packages->each(function (FunctionPackage $functionPackage) use ($functionEntry, $user) {
            $this->syncFunctionPackage($functionPackage, $user, (int) $functionEntry->venue_id);
        });
    }

    public function syncFunctionPackage(FunctionPackage $functionPackage, User $user, int $venueId): void
    {
        $functionPackage->loadMissing(['package.services', 'serviceLines']);

        $serviceIds = PackageServiceAssignment::query()
            ->where('user_id', $user->id)
            ->where('venue_id', $venueId)
            ->where('package_id', $functionPackage->package_id)
            ->pluck('service_id')
            ->map(fn ($serviceId) => (int) $serviceId)
            ->unique()
            ->values();

        if ($serviceIds->isEmpty()) {
            return;
        }

        $existingServiceIds = $functionPackage->serviceLines
            ->pluck('service_id')
            ->map(fn ($serviceId) => (int) $serviceId)
            ->all();

        $missingServiceIds = $serviceIds
            ->reject(fn (int $serviceId) => in_array($serviceId, $existingServiceIds, true))
            ->values();

        if ($missingServiceIds->isEmpty()) {
            return;
        }

        $services = $functionPackage->package?->services
            ?->whereIn('id', $missingServiceIds->all())
            ?->sortBy(fn (Service $service) => (int) ($service->pivot->sort_order ?? PHP_INT_MAX))
            ?->values()
            ?? Collection::make();

        if ($services->isEmpty()) {
            return;
        }

        $nextSortOrder = ((int) $functionPackage->serviceLines->max('sort_order')) + 1;

        $services->each(function (Service $service) use ($functionPackage, &$nextSortOrder) {
            $personInputMode = $service->person_input_mode ?: ($service->uses_persons ? Service::PERSON_MODE_FIXED : Service::PERSON_MODE_NONE);

            $functionPackage->serviceLines()->create([
                'service_id' => $service->id,
                'sort_order' => $nextSortOrder++,
                'item_name_snapshot' => $service->name,
                'rate_minor' => $service->standard_rate_minor,
                'uses_persons' => $personInputMode !== Service::PERSON_MODE_NONE,
                'person_input_mode' => $personInputMode,
                'persons' => match ($personInputMode) {
                    Service::PERSON_MODE_FIXED => (int) ($service->default_persons ?? 1),
                    Service::PERSON_MODE_EMPLOYEE => 1,
                    default => 0,
                },
                'is_selected' => false,
                'extra_charge_minor' => 0,
                'line_total_minor' => 0,
            ]);
        });
    }
}
