<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\UpdateEmployeeAssignmentsRequest;
use App\Models\Package;
use App\Models\PackageAssignment;
use App\Models\PackageServiceAssignment;
use App\Models\Service;
use App\Models\ServiceAssignment;
use App\Models\User;
use App\Models\Venue;
use App\Services\Files\AttachmentService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeAssignmentController extends Controller
{
    private const ASSIGNED_SERVICE_PAGE_SIZE = 20;
    private const CATALOG_PAGE_SIZE = 20;

    public function __construct(private AttachmentService $attachmentService)
    {
    }

    public function edit(Request $request, User $employee): View
    {
        abort_if($employee->isAdmin(), 404);

        $employee->load(['venues' => fn ($query) => $query->orderBy('name')]);

        $assignedVenues = $employee->venues->values();
        $selectedVenue = $assignedVenues->firstWhere('id', (int) $request->integer('venue'))
            ?? $assignedVenues->first();

        $packageAssignments = collect();
        $serviceAssignments = null;
        $serviceCountsByPackage = collect();
        $selectedPackageAssignment = null;
        $selectedPackageServiceCount = 0;
        $selectedPackageAssignedServiceIds = [];
        $selectedPackageServiceFilters = [
            'search' => trim((string) $request->string('service_search')),
        ];
        $catalogFilters = [
            'venue_search' => trim((string) $request->string('venue_search')),
            'package_search' => trim((string) $request->string('package_search')),
            'available_service_search' => trim((string) $request->string('available_service_search')),
        ];

        if ($selectedVenue) {
            $packageAssignments = PackageAssignment::query()
                ->where('user_id', $employee->id)
                ->where('venue_id', $selectedVenue->id)
                ->with('package')
                ->get()
                ->sortBy(fn (PackageAssignment $assignment) => strtolower((string) $assignment->package?->name))
                ->values();

            $serviceCountsByPackage = PackageServiceAssignment::query()
                ->where('user_id', $employee->id)
                ->where('venue_id', $selectedVenue->id)
                ->selectRaw('package_id, COUNT(*) as aggregate')
                ->groupBy('package_id')
                ->pluck('aggregate', 'package_id');

            $selectedPackageAssignment = $packageAssignments->firstWhere('package_id', (int) $request->integer('package'))
                ?? $packageAssignments->first();

            if ($selectedPackageAssignment) {
                $selectedPackageAssignedServiceIds = PackageServiceAssignment::query()
                    ->where('user_id', $employee->id)
                    ->where('venue_id', $selectedVenue->id)
                    ->where('package_id', $selectedPackageAssignment->package_id)
                    ->pluck('service_id')
                    ->map(fn ($serviceId) => (int) $serviceId)
                    ->all();

                $serviceAssignmentsQuery = PackageServiceAssignment::query()
                    ->where('user_id', $employee->id)
                    ->where('venue_id', $selectedVenue->id)
                    ->where('package_id', $selectedPackageAssignment->package_id)
                    ->join('services', 'services.id', '=', 'package_service_assignments.service_id')
                    ->select('package_service_assignments.*')
                    ->when($selectedPackageServiceFilters['search'] !== '', function ($query) use ($selectedPackageServiceFilters) {
                        $search = $selectedPackageServiceFilters['search'];

                        $query->where(function ($builder) use ($search) {
                            $builder
                                ->where('services.name', 'like', "%{$search}%")
                                ->orWhere('services.code', 'like', "%{$search}%")
                                ->orWhere('services.notes', 'like', "%{$search}%");
                        });
                    })
                    ->with('service.attachments')
                    ->orderByDesc('services.is_active')
                    ->orderBy('services.name');

                $selectedPackageServiceCount = (clone $serviceAssignmentsQuery)->count();
                $serviceAssignments = $serviceAssignmentsQuery
                    ->paginate(self::ASSIGNED_SERVICE_PAGE_SIZE, ['package_service_assignments.*'], 'service_page')
                    ->withQueryString();
            }
        }

        $packageIdsInVenue = $packageAssignments->pluck('package_id')->all();
        $serviceIdsInPackage = $selectedPackageAssignedServiceIds;
        $availableVenues = Venue::query()
            ->active()
            ->whereNotIn('id', $assignedVenues->pluck('id'))
            ->when($catalogFilters['venue_search'] !== '', function ($query) use ($catalogFilters) {
                $search = $catalogFilters['venue_search'];

                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(self::CATALOG_PAGE_SIZE, ['*'], 'available_venue_page')
            ->withQueryString();

        $availablePackages = $selectedVenue
            ? Package::query()
                ->active()
                ->whereNotIn('id', $packageIdsInVenue)
                ->when($catalogFilters['package_search'] !== '', function ($query) use ($catalogFilters) {
                    $search = $catalogFilters['package_search'];

                    $query->where(function ($builder) use ($search) {
                        $builder
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(self::CATALOG_PAGE_SIZE, ['*'], 'available_package_page')
                ->withQueryString()
            : $this->emptyCatalogPaginator('available_package_page');

        $availableServices = $selectedPackageAssignment
            ? Service::query()
                ->active()
                ->whereNotIn('id', $serviceIdsInPackage)
                ->when($catalogFilters['available_service_search'] !== '', function ($query) use ($catalogFilters) {
                    $search = $catalogFilters['available_service_search'];

                    $query->where(function ($builder) use ($search) {
                        $builder
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('notes', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(self::CATALOG_PAGE_SIZE, ['*'], 'available_service_page')
                ->withQueryString()
            : $this->emptyCatalogPaginator('available_service_page');

        return view('admin.master-data.employees.assignments', [
            'employee' => $employee,
            'assignedVenues' => $assignedVenues,
            'availableVenues' => $availableVenues,
            'availablePackages' => $availablePackages,
            'availableServices' => $availableServices,
            'packageAssignments' => $packageAssignments,
            'selectedVenue' => $selectedVenue,
            'selectedPackageAssignment' => $selectedPackageAssignment,
            'serviceAssignments' => $serviceAssignments,
            'selectedPackageServiceCount' => $selectedPackageServiceCount,
            'selectedPackageServiceFilters' => $selectedPackageServiceFilters,
            'catalogFilters' => $catalogFilters,
            'serviceCountsByPackage' => $serviceCountsByPackage,
            'selectedFrozenFund' => $selectedVenue ? Money::formatMinor($employee->frozenFundMinorForVenue($selectedVenue->id)) : Money::formatMinor(0),
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
        $packageServiceIdsByVenue = collect($request->validated('package_service_ids_by_venue', []));

        DB::transaction(function () use (
            $employee,
            $venueIds,
            $frozenFunds,
            $serviceIdsByVenue,
            $packageIdsByVenue,
            $packageServiceIdsByVenue
        ) {
            $venueSyncData = $venueIds->mapWithKeys(function (int $venueId) use ($employee, $frozenFunds) {
                return [$venueId => [
                    'frozen_fund_minor' => $employee->supportsFrozenFund()
                        ? Money::toMinor($frozenFunds->get($venueId))
                        : 0,
                ]];
            })->all();

            $currentVenueIds = $employee->venues()->pluck('venues.id')->all();
            $removedVenueIds = array_values(array_diff($currentVenueIds, $venueIds->all()));

            $employee->venues()->sync($venueSyncData);

            if ($removedVenueIds !== []) {
                ServiceAssignment::query()->where('user_id', $employee->id)->whereIn('venue_id', $removedVenueIds)->delete();
                PackageAssignment::query()->where('user_id', $employee->id)->whereIn('venue_id', $removedVenueIds)->delete();
                PackageServiceAssignment::query()->where('user_id', $employee->id)->whereIn('venue_id', $removedVenueIds)->delete();
            }

            PackageAssignment::query()->where('user_id', $employee->id)->delete();
            PackageServiceAssignment::query()->where('user_id', $employee->id)->delete();

            foreach ($venueIds as $venueId) {
                $selectedPackageIds = collect($packageIdsByVenue->get((string) $venueId, []))
                    ->map(fn ($packageId) => (int) $packageId)
                    ->unique()
                    ->values();

                $packageRows = $selectedPackageIds
                    ->map(fn ($packageId) => [
                        'user_id' => $employee->id,
                        'venue_id' => $venueId,
                        'package_id' => $packageId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                    ->all();

                if ($packageRows !== []) {
                    PackageAssignment::insert($packageRows);
                }

                $packageServiceRows = [];
                foreach ($selectedPackageIds as $packageId) {
                    $selectedServiceIds = collect(data_get($packageServiceIdsByVenue->all(), $venueId.'.'.$packageId, []))
                        ->map(fn ($serviceId) => (int) $serviceId)
                        ->unique()
                        ->values();

                    foreach ($selectedServiceIds as $serviceId) {
                        $packageServiceRows[] = [
                            'user_id' => $employee->id,
                            'venue_id' => $venueId,
                            'package_id' => $packageId,
                            'service_id' => $serviceId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if ($packageServiceRows !== []) {
                    PackageServiceAssignment::insert($packageServiceRows);
                }

                $manualServiceIds = collect($serviceIdsByVenue->get((string) $venueId, []))
                    ->map(fn ($serviceId) => (int) $serviceId)
                    ->unique();

                $serviceIds = collect($packageServiceRows)
                    ->pluck('service_id')
                    ->merge($manualServiceIds)
                    ->unique()
                    ->values();

                $serviceRows = $serviceIds->map(fn ($serviceId) => [
                    'user_id' => $employee->id,
                    'venue_id' => $venueId,
                    'service_id' => $serviceId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                if ($serviceRows !== []) {
                    ServiceAssignment::insert($serviceRows);
                }
            }
        });

        return redirect()
            ->route('admin.master-data.employees.assignments.edit', $employee)
            ->with('status', 'Employee setup updated.');
    }

    public function storeVenue(Request $request, User $employee): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);

        $data = $this->validateWithBag('createVenue', $request, [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('venues', 'code')],
            'vendor_slots' => ['required', 'array', 'size:4'],
            'vendor_slots.*' => ['nullable', 'string', 'max:120'],
            'frozen_fund' => ['nullable', 'numeric', 'min:0'],
        ]);

        $venue = DB::transaction(function () use ($employee, $data) {
            $venue = Venue::query()->create([
                'name' => $data['name'],
                'code' => $data['code'] ?: null,
                'is_active' => true,
            ]);

            $this->syncVendorSlots($venue, $data['vendor_slots']);
            $employee->venues()->syncWithoutDetaching([
                $venue->id => [
                    'frozen_fund_minor' => $employee->supportsFrozenFund()
                        ? Money::toMinor($data['frozen_fund'] ?? null)
                        : 0,
                ],
            ]);

            return $venue;
        });

        return $this->redirectToWorkspace($employee, $venue->id, null, 'Venue created and assigned to this employee.');
    }

    public function attachVenue(Request $request, User $employee): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);

        $data = $this->validateWithBag('attachVenue', $request, [
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'frozen_fund' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employee->venues()->syncWithoutDetaching([
            (int) $data['venue_id'] => [
                'frozen_fund_minor' => $employee->supportsFrozenFund()
                    ? Money::toMinor($data['frozen_fund'] ?? null)
                    : 0,
            ],
        ]);

        return $this->redirectToWorkspace($employee, (int) $data['venue_id'], null, 'Venue assigned to this employee.');
    }

    public function updateVenue(Request $request, User $employee, Venue $venue): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);
        $this->employeeVenueOrFail($employee, $venue);

        $data = $request->validate([
            'frozen_fund' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employee->venues()->updateExistingPivot($venue->id, [
            'frozen_fund_minor' => $employee->supportsFrozenFund()
                ? Money::toMinor($data['frozen_fund'] ?? null)
                : 0,
        ]);

        return $this->redirectToWorkspace($employee, $venue->id, null, 'Venue settings updated.');
    }

    public function destroyVenue(User $employee, Venue $venue): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);
        $this->employeeVenueOrFail($employee, $venue);

        DB::transaction(function () use ($employee, $venue) {
            $employee->venues()->detach($venue->id);
            PackageAssignment::query()->where('user_id', $employee->id)->where('venue_id', $venue->id)->delete();
            PackageServiceAssignment::query()->where('user_id', $employee->id)->where('venue_id', $venue->id)->delete();
            ServiceAssignment::query()->where('user_id', $employee->id)->where('venue_id', $venue->id)->delete();
        });

        return redirect()
            ->route('admin.master-data.employees.assignments.edit', $employee)
            ->with('status', 'Venue removed from this employee.');
    }

    public function storePackage(Request $request, User $employee, Venue $venue): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);
        $this->employeeVenueOrFail($employee, $venue);

        $data = $this->validateWithBag('createPackage', $request, [
            'name' => ['required', 'string', 'max:120', Rule::unique('packages', 'name')],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('packages', 'code')],
            'description' => ['nullable', 'string'],
        ]);

        $package = DB::transaction(function () use ($employee, $venue, $data) {
            $package = Package::query()->create([
                'name' => $data['name'],
                'code' => $data['code'] ?: null,
                'description' => $data['description'] ?? null,
                'is_active' => true,
            ]);

            PackageAssignment::query()->firstOrCreate([
                'user_id' => $employee->id,
                'venue_id' => $venue->id,
                'package_id' => $package->id,
            ]);

            return $package;
        });

        return $this->redirectToWorkspace($employee, $venue->id, $package->id, 'Package created for this employee venue.');
    }

    public function attachPackage(Request $request, User $employee, Venue $venue): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);
        $this->employeeVenueOrFail($employee, $venue);

        $data = $this->validateWithBag('attachPackage', $request, [
            'package_id' => ['required', 'integer', 'exists:packages,id'],
        ]);

        $package = Package::query()->findOrFail((int) $data['package_id']);

        DB::transaction(function () use ($employee, $venue, $package) {
            PackageAssignment::query()->firstOrCreate([
                'user_id' => $employee->id,
                'venue_id' => $venue->id,
                'package_id' => $package->id,
            ]);

            $packageServiceRows = $package->services()
                ->where('services.is_active', true)
                ->pluck('services.id')
                ->map(fn ($serviceId) => [
                    'user_id' => $employee->id,
                    'venue_id' => $venue->id,
                    'package_id' => $package->id,
                    'service_id' => (int) $serviceId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all();

            if ($packageServiceRows !== []) {
                PackageServiceAssignment::query()->upsert(
                    $packageServiceRows,
                    ['user_id', 'venue_id', 'package_id', 'service_id'],
                    ['updated_at']
                );
            }

            $this->syncDerivedServiceAssignments($employee, $venue);
        });

        return $this->redirectToWorkspace($employee, $venue->id, $package->id, 'Package assigned with its mapped active services. Remove any services you do not want for this employee.');
    }

    public function destroyPackage(User $employee, Venue $venue, Package $package): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);
        $this->employeeVenueOrFail($employee, $venue);
        $this->employeePackageOrFail($employee, $venue, $package);

        DB::transaction(function () use ($employee, $venue, $package) {
            PackageAssignment::query()
                ->where('user_id', $employee->id)
                ->where('venue_id', $venue->id)
                ->where('package_id', $package->id)
                ->delete();

            PackageServiceAssignment::query()
                ->where('user_id', $employee->id)
                ->where('venue_id', $venue->id)
                ->where('package_id', $package->id)
                ->delete();

            $this->syncDerivedServiceAssignments($employee, $venue);
        });

        return $this->redirectToWorkspace($employee, $venue->id, null, 'Package removed from this employee venue.');
    }

    public function storeService(Request $request, User $employee, Venue $venue, Package $package): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);
        $this->employeeVenueOrFail($employee, $venue);
        $this->employeePackageOrFail($employee, $venue, $package);

        $data = $this->validateWithBag('createService', $request, [
            'name' => ['required', 'string', 'max:120', Rule::unique('services', 'name')],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('services', 'code')],
            'standard_rate' => ['required', 'numeric', 'min:0'],
            'person_input_mode' => ['nullable', Rule::in([
                Service::PERSON_MODE_FIXED,
                Service::PERSON_MODE_EMPLOYEE,
                Service::PERSON_MODE_NONE,
            ])],
            'default_persons' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:25600', 'mimes:jpg,jpeg,png,gif,webp,bmp,svg,avif,heic,heif,tif,tiff,pdf,doc,docx,odt,xls,xlsx,ods,csv,odf'],
        ], [
            'attachments.*.mimes' => 'Only image files, PDF, CSV, Excel, Word, and ODF documents can be attached. Files like .css or .txt are not allowed.',
            'attachments.*.max' => 'Each attachment must be 25 MB or smaller.',
            'attachments.*.file' => 'Each selected attachment must be a valid file.',
        ]);

        $service = DB::transaction(function () use ($request, $employee, $venue, $package, $data) {
            $personInputMode = $data['person_input_mode'] ?? Service::PERSON_MODE_FIXED;

            $service = Service::query()->create([
                'name' => $data['name'],
                'code' => $data['code'] ?: null,
                'standard_rate_minor' => Money::toMinor($data['standard_rate']),
                'uses_persons' => $personInputMode !== Service::PERSON_MODE_NONE,
                'person_input_mode' => $personInputMode,
                'default_persons' => $personInputMode === Service::PERSON_MODE_FIXED
                    ? max(1, (int) ($data['default_persons'] ?? 1))
                    : null,
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
            ]);

            $this->attachmentService->storeFor($service, $request->file('attachments', []), $request->user());

            $package->services()->syncWithoutDetaching([
                $service->id => ['sort_order' => $this->nextPackageSortOrder($package)],
            ]);

            PackageServiceAssignment::query()->firstOrCreate([
                'user_id' => $employee->id,
                'venue_id' => $venue->id,
                'package_id' => $package->id,
                'service_id' => $service->id,
            ]);

            $this->syncDerivedServiceAssignments($employee, $venue);

            return $service;
        });

        return $this->redirectToWorkspace($employee, $venue->id, $package->id, 'Service created and linked to this package.');
    }

    public function attachService(Request $request, User $employee, Venue $venue, Package $package): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);
        $this->employeeVenueOrFail($employee, $venue);
        $this->employeePackageOrFail($employee, $venue, $package);

        $data = $this->validateWithBag('attachService', $request, [
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ]);

        DB::transaction(function () use ($employee, $venue, $package, $data) {
            $serviceIds = collect($data['service_ids'])->map(fn ($serviceId) => (int) $serviceId)->unique()->values();

            foreach ($serviceIds as $serviceId) {
                $package->services()->syncWithoutDetaching([
                    $serviceId => ['sort_order' => $this->nextPackageSortOrder($package, $serviceId)],
                ]);

                PackageServiceAssignment::query()->firstOrCreate([
                    'user_id' => $employee->id,
                    'venue_id' => $venue->id,
                    'package_id' => $package->id,
                    'service_id' => $serviceId,
                ]);
            }

            $this->syncDerivedServiceAssignments($employee, $venue);
        });

        return $this->redirectToWorkspace($employee, $venue->id, $package->id, 'Services assigned to this package.');
    }

    public function destroyService(User $employee, Venue $venue, Package $package, Service $service): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);
        $this->employeeVenueOrFail($employee, $venue);
        $this->employeePackageOrFail($employee, $venue, $package);

        DB::transaction(function () use ($employee, $venue, $package, $service) {
            PackageServiceAssignment::query()
                ->where('user_id', $employee->id)
                ->where('venue_id', $venue->id)
                ->where('package_id', $package->id)
                ->where('service_id', $service->id)
                ->delete();

            $this->syncDerivedServiceAssignments($employee, $venue);
        });

        return $this->redirectToWorkspace($employee, $venue->id, $package->id, 'Service removed from this employee package.');
    }

    public function destroyServices(Request $request, User $employee, Venue $venue, Package $package): RedirectResponse
    {
        abort_if($employee->isAdmin(), 404);
        $this->employeeVenueOrFail($employee, $venue);
        $this->employeePackageOrFail($employee, $venue, $package);

        $data = $request->validate([
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ]);

        DB::transaction(function () use ($employee, $venue, $package, $data) {
            PackageServiceAssignment::query()
                ->where('user_id', $employee->id)
                ->where('venue_id', $venue->id)
                ->where('package_id', $package->id)
                ->whereIn('service_id', collect($data['service_ids'])->map(fn ($serviceId) => (int) $serviceId))
                ->delete();

            $this->syncDerivedServiceAssignments($employee, $venue);
        });

        return $this->redirectToWorkspace($employee, $venue->id, $package->id, 'Selected services removed from this employee package.');
    }

    private function employeeVenueOrFail(User $employee, Venue $venue): void
    {
        abort_unless($employee->venues()->whereKey($venue->id)->exists(), 404);
    }

    private function employeePackageOrFail(User $employee, Venue $venue, Package $package): void
    {
        abort_unless(
            PackageAssignment::query()
                ->where('user_id', $employee->id)
                ->where('venue_id', $venue->id)
                ->where('package_id', $package->id)
                ->exists(),
            404
        );
    }

    private function redirectToWorkspace(User $employee, ?int $venueId, ?int $packageId, string $status): RedirectResponse
    {
        return redirect()
            ->route('admin.master-data.employees.assignments.edit', array_filter([
                'employee' => $employee,
                'venue' => $venueId,
                'package' => $packageId,
            ], fn ($value) => ! is_null($value)))
            ->with('status', $status);
    }

    private function emptyCatalogPaginator(string $pageName): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            self::CATALOG_PAGE_SIZE,
            1,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ]
        );
    }

    private function syncVendorSlots(Venue $venue, array $vendorSlots): void
    {
        foreach (range(1, 4) as $slotNumber) {
            $name = trim((string) ($vendorSlots[$slotNumber] ?? ''));

            $venue->vendors()->updateOrCreate(
                ['slot_number' => $slotNumber],
                ['name' => $name !== '' ? $name : "Vendor {$slotNumber}"]
            );
        }
    }

    private function nextPackageSortOrder(Package $package, ?int $serviceId = null): int
    {
        if ($serviceId && $existing = $package->services()->where('services.id', $serviceId)->first()) {
            return (int) $existing->pivot->sort_order;
        }

        $max = $package->services()->max('package_service.sort_order');

        return ((int) $max) + 1 ?: 1;
    }

    private function syncDerivedServiceAssignments(User $employee, Venue $venue): void
    {
        $serviceIds = PackageServiceAssignment::query()
            ->where('user_id', $employee->id)
            ->where('venue_id', $venue->id)
            ->pluck('service_id')
            ->map(fn ($serviceId) => (int) $serviceId)
            ->unique()
            ->values();

        ServiceAssignment::query()
            ->where('user_id', $employee->id)
            ->where('venue_id', $venue->id)
            ->delete();

        if ($serviceIds->isEmpty()) {
            return;
        }

        ServiceAssignment::insert($serviceIds->map(fn ($serviceId) => [
            'user_id' => $employee->id,
            'venue_id' => $venue->id,
            'service_id' => $serviceId,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }
}
