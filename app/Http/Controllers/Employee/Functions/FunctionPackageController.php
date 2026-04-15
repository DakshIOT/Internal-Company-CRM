<?php

namespace App\Http\Controllers\Employee\Functions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Functions\FunctionPackageLinesRequest;
use App\Http\Requests\Employee\Functions\FunctionPackageRequest;
use App\Models\FunctionEntry;
use App\Models\FunctionPackage;
use App\Models\Package;
use App\Models\PackageServiceAssignment;
use App\Models\Service;
use App\Services\Functions\FunctionPackageAvailabilitySyncService;
use App\Services\Functions\FunctionEntryTotalsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FunctionPackageController extends Controller
{
    public function __construct(
        private FunctionEntryTotalsService $totalsService,
        private FunctionPackageAvailabilitySyncService $availabilitySyncService,
    )
    {
    }

    public function store(FunctionPackageRequest $request, FunctionEntry $functionEntry): RedirectResponse
    {
        $this->authorizeEntry($request, $functionEntry);

        $package = $this->assignedPackageOrFail($request, $functionEntry, (int) $request->validated('package_id'));

        if ($functionEntry->packages()->where('package_id', $package->id)->exists()) {
            throw ValidationException::withMessages([
                'package_id' => 'This package is already attached to the function entry.',
            ]);
        }

        DB::transaction(function () use ($request, $functionEntry, $package) {
            $functionPackage = $functionEntry->packages()->create([
                'package_id' => $package->id,
                'name_snapshot' => $package->name,
                'code_snapshot' => $package->code,
            ]);

            $packageSpecificServiceIds = PackageServiceAssignment::query()
                ->where('user_id', $request->user()->id)
                ->where('venue_id', $functionEntry->venue_id)
                ->where('package_id', $package->id)
                ->pluck('service_id');

            $services = $package->services()
                ->whereIn('services.id', $packageSpecificServiceIds)
                ->get([
                    'services.id',
                    'services.name',
                    'services.standard_rate_minor',
                    'services.uses_persons',
                    'services.person_input_mode',
                    'services.default_persons',
                ]);

            if ($services->isEmpty()) {
                throw ValidationException::withMessages([
                    'package_id' => 'This package has no assigned services for your current venue.',
                ]);
            }

            $services->each(function (Service $service, int $index) use ($functionPackage) {
                $personInputMode = $service->person_input_mode ?: ($service->uses_persons ? Service::PERSON_MODE_FIXED : Service::PERSON_MODE_NONE);

                $functionPackage->serviceLines()->create([
                    'service_id' => $service->id,
                    'sort_order' => $index + 1,
                    'item_name_snapshot' => $service->name,
                    'rate_minor' => $service->standard_rate_minor,
                    'uses_persons' => $personInputMode !== Service::PERSON_MODE_NONE,
                    'person_input_mode' => $personInputMode,
                    'persons' => match ($personInputMode) {
                        Service::PERSON_MODE_FIXED => (int) ($service->default_persons ?? 1),
                        Service::PERSON_MODE_EMPLOYEE => 1,
                        default => 0,
                    },
                ]);
            });

            $this->totalsService->recalculate($functionEntry);
        });

        return $this->backToActionCenter($functionEntry, 'packages', 'Package added to the action center.');
    }

    public function update(
        FunctionPackageLinesRequest $request,
        FunctionEntry $functionEntry,
        FunctionPackage $functionPackage
    ): RedirectResponse {
        $this->authorizeEntry($request, $functionEntry);
        $functionPackage = $this->resolvePackage($functionEntry, $functionPackage);

        $lines = $functionPackage->serviceLines()->get()->keyBy('id');

        DB::transaction(function () use ($request, $functionEntry, $lines) {
            foreach ($request->validated('service_lines') as $lineId => $payload) {
                $serviceLine = $lines->get((int) $lineId);

                if (! $serviceLine) {
                    continue;
                }

                $personInputMode = $serviceLine->person_input_mode
                    ?: ($serviceLine->uses_persons ? Service::PERSON_MODE_FIXED : Service::PERSON_MODE_NONE);
                $persons = match ($personInputMode) {
                    Service::PERSON_MODE_FIXED => (int) $serviceLine->persons,
                    Service::PERSON_MODE_EMPLOYEE => max(0, (int) ($payload['persons'] ?? 0)),
                    default => 0,
                };
                $rateMinor = (int) $serviceLine->rate_minor;
                $extraChargeMinor = Money::toMinor($payload['extra_charge'] ?? 0);

                $serviceLine->update([
                    'is_selected' => (bool) ($payload['is_selected'] ?? false),
                    'persons' => $persons,
                    'rate_minor' => $rateMinor,
                    'extra_charge_minor' => $extraChargeMinor,
                    'notes' => $payload['notes'] ?? null,
                    'line_total_minor' => $this->totalsService->calculateLineTotalMinor($personInputMode, $persons, $rateMinor, $extraChargeMinor),
                ]);
            }

            $this->totalsService->recalculate($functionEntry);
        });

        return $this->backToActionCenter($functionEntry, 'packages', 'Package lines updated.');
    }

    public function destroy(Request $request, FunctionEntry $functionEntry, FunctionPackage $functionPackage): RedirectResponse
    {
        $this->authorizeEntry($request, $functionEntry);
        $functionPackage = $this->resolvePackage($functionEntry, $functionPackage);

        DB::transaction(function () use ($functionPackage, $functionEntry) {
            $functionPackage->delete();
            $this->totalsService->recalculate($functionEntry);
        });

        return $this->backToActionCenter($functionEntry, 'packages', 'Package removed.');
    }

    private function authorizeEntry(Request $request, FunctionEntry $functionEntry): void
    {
        $this->authorize('update', $functionEntry);
        abort_unless((int) $functionEntry->venue_id === (int) $request->session()->get('selected_venue_id'), 404);
    }

    private function assignedPackageOrFail(Request $request, FunctionEntry $functionEntry, int $packageId): Package
    {
        return Package::query()
            ->select('packages.*')
            ->join('package_assignments', function ($join) use ($request, $functionEntry) {
                $join->on('package_assignments.package_id', '=', 'packages.id')
                    ->where('package_assignments.user_id', '=', $request->user()->id)
                    ->where('package_assignments.venue_id', '=', $functionEntry->venue_id);
            })
            ->where('packages.id', $packageId)
            ->where('packages.is_active', true)
            ->with(['services' => fn ($query) => $query->active()])
            ->firstOrFail();
    }

    private function resolvePackage(FunctionEntry $functionEntry, FunctionPackage $functionPackage): FunctionPackage
    {
        abort_unless((int) $functionPackage->function_entry_id === (int) $functionEntry->id, 404);

        $functionPackage->loadMissing(['package.services', 'serviceLines']);
        $this->availabilitySyncService->syncFunctionPackage($functionPackage, $functionEntry->user, (int) $functionEntry->venue_id);

        return $functionPackage->fresh(['serviceLines.service.attachments']);
    }

    private function backToActionCenter(FunctionEntry $functionEntry, string $tab, string $status): RedirectResponse
    {
        return redirect()
            ->route('employee.functions.edit', ['functionEntry' => $functionEntry->id, 'tab' => $tab])
            ->with('status', $status);
    }
}
