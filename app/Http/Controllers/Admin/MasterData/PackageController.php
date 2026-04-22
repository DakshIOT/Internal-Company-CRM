<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\StorePackageRequest;
use App\Http\Requests\Admin\MasterData\UpdatePackageRequest;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PackageController extends Controller
{
    private const SERVICE_CATALOG_PAGE_SIZE = 20;

    public function index(Request $request): View
    {
        $query = Package::query()->withCount('services');

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->value()) {
            $query->where('is_active', $status === 'active');
        }

        return view('admin.master-data.packages.index', [
            'filters' => $request->only(['search', 'status']),
            'packages' => $query->orderByDesc('is_active')->orderBy('name')->paginate(12)->withQueryString(),
            'stats' => [
                'total' => Package::count(),
                'active' => Package::where('is_active', true)->count(),
                'mapped_services' => DB::table('package_service')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $serviceFilters = [
            'search' => trim((string) $request->string('service_search')),
        ];

        $serviceCatalog = Service::query()
            ->active()
            ->when($serviceFilters['search'] !== '', function ($query) use ($serviceFilters) {
                $search = $serviceFilters['search'];

                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(self::SERVICE_CATALOG_PAGE_SIZE, ['*'], 'service_page')
            ->withQueryString();

        return view('admin.master-data.packages.form', [
            'isEditing' => false,
            'package' => new Package(['is_active' => true]),
            'selectedServiceIds' => collect(old('service_ids', []))->map(fn ($serviceId) => (int) $serviceId)->all(),
            'sortOrders' => collect(old('sort_orders', []))->mapWithKeys(fn ($sortOrder, $serviceId) => [(int) $serviceId => $sortOrder])->all(),
            'serviceFilters' => $serviceFilters,
            'serviceCatalog' => $serviceCatalog,
            'mappedServicesCount' => count(old('service_ids', [])),
        ]);
    }

    public function store(StorePackageRequest $request): RedirectResponse
    {
        $package = Package::create([
            'name' => $request->validated('name'),
            'code' => $request->validated('code'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncServices($package, $request->validated('service_ids', []), $request->validated('sort_orders', []));

        return redirect()
            ->route('admin.master-data.packages.edit', $package)
            ->with('status', 'Package created successfully. Continue mapping services below.');
    }

    public function edit(Request $request, Package $package): View
    {
        $package->load('services');
        $mappedServiceIds = $package->services->pluck('id')->all();
        $serviceFilters = [
            'search' => trim((string) $request->string('service_search')),
        ];

        $serviceCatalog = Service::query()
            ->when($serviceFilters['search'] !== '', function ($query) use ($serviceFilters) {
                $search = $serviceFilters['search'];

                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->where(function ($query) use ($mappedServiceIds) {
                $query->where('is_active', true);

                if ($mappedServiceIds !== []) {
                    $query->orWhereIn('id', $mappedServiceIds);
                }
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(self::SERVICE_CATALOG_PAGE_SIZE, ['*'], 'service_page')
            ->withQueryString();

        return view('admin.master-data.packages.form', [
            'isEditing' => true,
            'package' => $package,
            'selectedServiceIds' => $mappedServiceIds,
            'sortOrders' => $package->services
                ->mapWithKeys(fn (Service $service) => [$service->id => $service->pivot->sort_order])
                ->all(),
            'serviceFilters' => $serviceFilters,
            'serviceCatalog' => $serviceCatalog,
            'mappedServicesCount' => count($mappedServiceIds),
        ]);
    }

    public function update(UpdatePackageRequest $request, Package $package): RedirectResponse
    {
        $package->update([
            'name' => $request->validated('name'),
            'code' => $request->validated('code'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.master-data.packages.edit', $package)
            ->with('status', 'Package updated successfully.');
    }

    public function updateMapping(Request $request, Package $package): RedirectResponse
    {
        $data = $request->validate([
            'visible_service_ids' => ['required', 'array', 'min:1'],
            'visible_service_ids.*' => ['integer', 'exists:services,id'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'sort_orders' => ['nullable', 'array'],
            'sort_orders.*' => ['nullable', 'integer', 'min:1'],
            'service_search' => ['nullable', 'string', 'max:120'],
            'service_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $visibleServiceIds = collect($data['visible_service_ids'])
            ->map(fn ($serviceId) => (int) $serviceId)
            ->unique()
            ->values();
        $selectedServiceIds = collect($data['service_ids'] ?? [])
            ->map(fn ($serviceId) => (int) $serviceId)
            ->intersect($visibleServiceIds)
            ->values();
        $selectedLookup = $selectedServiceIds->flip();

        DB::transaction(function () use ($package, $visibleServiceIds, $selectedServiceIds, $selectedLookup, $data) {
            if ($visibleServiceIds->isEmpty()) {
                return;
            }

            $existingSortOrders = $package->services()
                ->whereIn('services.id', $visibleServiceIds)
                ->pluck('package_service.sort_order', 'services.id');

            $detachIds = $visibleServiceIds->reject(fn (int $serviceId) => $selectedLookup->has($serviceId))->all();

            if ($detachIds !== []) {
                $package->services()->detach($detachIds);
            }

            $syncData = $selectedServiceIds->mapWithKeys(function (int $serviceId, int $index) use ($data, $existingSortOrders) {
                return [
                    $serviceId => [
                        'sort_order' => max(
                            1,
                            (int) ($data['sort_orders'][$serviceId] ?? $existingSortOrders[$serviceId] ?? ($index + 1))
                        ),
                    ],
                ];
            })->all();

            if ($syncData !== []) {
                $package->services()->syncWithoutDetaching($syncData);
            }
        });

        return redirect()
            ->route('admin.master-data.packages.edit', [
                'package' => $package,
                'service_search' => $data['service_search'] ?? null,
                'service_page' => $data['service_page'] ?? null,
            ])
            ->with('status', 'Package service mapping updated.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        if ($package->functionPackages()->exists()) {
            return redirect()
                ->route('admin.master-data.packages.index')
                ->with('error', 'This package is already used in Function Entry records and cannot be deleted.');
        }

        if ($package->assignments()->exists() || $package->serviceAssignments()->exists()) {
            return redirect()
                ->route('admin.master-data.packages.index')
                ->with('error', 'This package is still assigned to employee setup records. Remove those assignments first.');
        }

        $package->delete();

        return redirect()
            ->route('admin.master-data.packages.index')
            ->with('status', 'Package deleted successfully.');
    }

    public function toggleActive(Package $package): RedirectResponse
    {
        $package->update([
            'is_active' => ! $package->is_active,
        ]);

        return redirect()
            ->route('admin.master-data.packages.index')
            ->with('status', $package->is_active
                ? 'Package activated successfully.'
                : 'Package deactivated successfully.');
    }

    protected function syncServices(Package $package, array $serviceIds, array $sortOrders): void
    {
        $syncData = collect($serviceIds)
            ->map(fn ($serviceId) => (int) $serviceId)
            ->unique()
            ->values()
            ->mapWithKeys(fn (int $serviceId, int $index) => [
                $serviceId => ['sort_order' => max(1, (int) ($sortOrders[$serviceId] ?? $index + 1))],
            ])
            ->all();

        $package->services()->sync($syncData);
    }
}
