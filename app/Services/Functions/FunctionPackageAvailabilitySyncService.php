<?php

namespace App\Services\Functions;

use App\Models\FunctionEntry;
use App\Models\FunctionPackage;
use App\Models\FunctionServiceLine;
use App\Models\PackageServiceAssignment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;

class FunctionPackageAvailabilitySyncService
{
    public function __construct(private FunctionEntryTotalsService $totalsService)
    {
    }

    public function syncEntry(FunctionEntry $functionEntry, User $user): void
    {
        $functionEntry->loadMissing('packages.package');

        $changed = false;

        $functionEntry->packages->each(function (FunctionPackage $functionPackage) use ($functionEntry, $user, &$changed) {
            $changed = $this->syncFunctionPackage($functionPackage, $user, (int) $functionEntry->venue_id) || $changed;
        });

        if ($changed) {
            $this->totalsService->recalculate($functionEntry);
        }
    }

    public function syncFunctionPackage(FunctionPackage $functionPackage, User $user, int $venueId): bool
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
            return false;
        }

        $existingLines = $functionPackage->serviceLines->keyBy('service_id');
        $existingServiceIds = $existingLines
            ->pluck('service_id')
            ->map(fn ($serviceId) => (int) $serviceId)
            ->all();

        $missingServiceIds = $serviceIds
            ->reject(fn (int $serviceId) => in_array($serviceId, $existingServiceIds, true))
            ->values();

        $services = $functionPackage->package?->services
            ?->where('is_active', true)
            ?->whereIn('id', $serviceIds->all())
            ?->sortBy(fn (Service $service) => (int) ($service->pivot->sort_order ?? PHP_INT_MAX))
            ?->values()
            ?? Collection::make();

        if ($services->isEmpty()) {
            return false;
        }

        $changed = false;
        $nextSortOrder = ((int) $functionPackage->serviceLines->max('sort_order')) + 1;

        $services->each(function (Service $service) use ($existingLines, $functionPackage, &$nextSortOrder, &$changed) {
            $existingLine = $existingLines->get($service->id);

            if ($existingLine instanceof FunctionServiceLine) {
                $changed = $this->syncExistingLine($existingLine, $service) || $changed;

                return;
            }

            $functionPackage->serviceLines()->create($this->linePayloadForNewService($service, $nextSortOrder++));
            $changed = true;
        });

        return $changed;
    }

    private function syncExistingLine(FunctionServiceLine $line, Service $service): bool
    {
        $personInputMode = $service->person_input_mode ?: ($service->uses_persons ? Service::PERSON_MODE_FIXED : Service::PERSON_MODE_NONE);
        $persons = match ($personInputMode) {
            Service::PERSON_MODE_FIXED => (int) ($service->default_persons ?? 1),
            Service::PERSON_MODE_EMPLOYEE => max(1, (int) $line->persons),
            default => 0,
        };
        $rateMinor = (int) $service->standard_rate_minor;
        $extraChargeMinor = (int) $line->extra_charge_minor;
        $lineTotalMinor = $this->totalsService->calculateLineTotalMinor($personInputMode, $persons, $rateMinor, $extraChargeMinor);

        $payload = [
            'item_name_snapshot' => $service->name,
            'rate_minor' => $rateMinor,
            'uses_persons' => $personInputMode !== Service::PERSON_MODE_NONE,
            'person_input_mode' => $personInputMode,
            'persons' => $persons,
            'line_total_minor' => $lineTotalMinor,
        ];

        foreach ($payload as $key => $value) {
            if ((string) $line->{$key} !== (string) $value) {
                $line->update($payload);

                return true;
            }
        }

        return false;
    }

    private function linePayloadForNewService(Service $service, int $sortOrder): array
    {
        $personInputMode = $service->person_input_mode ?: ($service->uses_persons ? Service::PERSON_MODE_FIXED : Service::PERSON_MODE_NONE);

        return [
            'service_id' => $service->id,
            'sort_order' => $sortOrder,
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
        ];
    }
}
