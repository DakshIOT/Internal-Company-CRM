<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\StoreServiceRequest;
use App\Http\Requests\Admin\MasterData\UpdateServiceRequest;
use App\Models\Package;
use App\Models\Service;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Service::query()
            ->withCount(['packages', 'assignments'])
            ->with(['packages:id,name']);

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

        return view('admin.master-data.services.index', [
            'filters' => $request->only(['search', 'status']),
            'services' => $query->orderByDesc('is_active')->orderBy('name')->paginate(12)->withQueryString(),
            'stats' => [
                'total' => Service::count(),
                'active' => Service::where('is_active', true)->count(),
                'assigned' => Service::has('assignments')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.master-data.services.form', [
            'service' => new Service(['is_active' => true]),
            'isEditing' => false,
            'packages' => Package::query()->orderBy('name')->get(['id', 'name', 'code']),
            'selectedPackageIds' => [],
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data) {
            $service = Service::create([
                'name' => $data['name'],
                'code' => $data['code'] ?: null,
                'standard_rate_minor' => Money::toMinor($data['standard_rate']),
                'notes' => $data['notes'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncPackages($service, $data['package_ids'] ?? []);
        });

        return redirect()
            ->route('admin.master-data.services.index')
            ->with('status', 'Service created successfully.');
    }

    public function edit(Service $service): View
    {
        return view('admin.master-data.services.form', [
            'service' => $service->loadCount(['packages', 'assignments']),
            'isEditing' => true,
            'packages' => Package::query()->orderBy('name')->get(['id', 'name', 'code']),
            'selectedPackageIds' => $service->packages()->pluck('packages.id')->all(),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $service, $data) {
            $service->update([
                'name' => $data['name'],
                'code' => $data['code'] ?: null,
                'standard_rate_minor' => Money::toMinor($data['standard_rate']),
                'notes' => $data['notes'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncPackages($service, $data['package_ids'] ?? []);
        });

        return redirect()
            ->route('admin.master-data.services.edit', $service)
            ->with('status', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()
            ->route('admin.master-data.services.index')
            ->with('status', 'Service deleted successfully.');
    }

    protected function syncPackages(Service $service, array $packageIds): void
    {
        $selectedPackageIds = collect($packageIds)
            ->map(fn ($packageId) => (int) $packageId)
            ->unique()
            ->values();

        $syncData = $selectedPackageIds->mapWithKeys(function (int $packageId) use ($service) {
            $existingSortOrder = DB::table('package_service')
                ->where('package_id', $packageId)
                ->where('service_id', $service->id)
                ->value('sort_order');

            $nextSortOrder = DB::table('package_service')
                ->where('package_id', $packageId)
                ->max('sort_order');

            return [$packageId => [
                'sort_order' => $existingSortOrder ?: ((int) $nextSortOrder + 1 ?: 1),
            ]];
        })->all();

        $service->packages()->sync($syncData);
    }
}
