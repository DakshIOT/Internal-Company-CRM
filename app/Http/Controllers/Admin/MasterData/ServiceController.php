<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\StoreServiceRequest;
use App\Http\Requests\Admin\MasterData\UpdateServiceRequest;
use App\Models\Service;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Service::query()->withCount(['packages', 'assignments']);

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
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $service = Service::create([
            'name' => $data['name'],
            'code' => $data['code'] ?: null,
            'standard_rate_minor' => Money::toMinor($data['standard_rate']),
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.master-data.services.edit', $service)
            ->with('status', 'Service created successfully.');
    }

    public function edit(Service $service): View
    {
        return view('admin.master-data.services.form', [
            'service' => $service->loadCount(['packages', 'assignments']),
            'isEditing' => true,
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();

        $service->update([
            'name' => $data['name'],
            'code' => $data['code'] ?: null,
            'standard_rate_minor' => Money::toMinor($data['standard_rate']),
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

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
}
