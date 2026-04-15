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

    public function create(): View
    {
        return view('admin.master-data.packages.form', [
            'isEditing' => false,
            'package' => new Package(['is_active' => true]),
            'services' => Service::query()->orderBy('name')->get(),
            'selectedServiceIds' => [],
            'sortOrders' => [],
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
            ->route('admin.master-data.packages.index')
            ->with('status', 'Package created successfully.');
    }

    public function edit(Package $package): View
    {
        $package->load('services');

        return view('admin.master-data.packages.form', [
            'isEditing' => true,
            'package' => $package,
            'services' => Service::query()->orderBy('name')->get(),
            'selectedServiceIds' => $package->services->pluck('id')->all(),
            'sortOrders' => $package->services
                ->mapWithKeys(fn (Service $service) => [$service->id => $service->pivot->sort_order])
                ->all(),
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

        $this->syncServices($package, $request->validated('service_ids', []), $request->validated('sort_orders', []));

        return redirect()
            ->route('admin.master-data.packages.edit', $package)
            ->with('status', 'Package updated successfully.');
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
