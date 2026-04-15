<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\StoreServiceRequest;
use App\Http\Requests\Admin\MasterData\UpdateServiceRequest;
use App\Models\Attachment;
use App\Models\Package;
use App\Models\Service;
use App\Services\Files\AttachmentService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private AttachmentService $attachmentService)
    {
    }

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
        $personInputMode = $data['person_input_mode'] ?? Service::PERSON_MODE_FIXED;

        DB::transaction(function () use ($request, $data, $personInputMode) {
            $service = Service::create([
                'name' => $data['name'],
                'code' => $data['code'] ?: null,
                'standard_rate_minor' => Money::toMinor($data['standard_rate']),
                'uses_persons' => $personInputMode !== Service::PERSON_MODE_NONE,
                'person_input_mode' => $personInputMode,
                'default_persons' => $personInputMode === Service::PERSON_MODE_FIXED
                    ? max(1, (int) ($data['default_persons'] ?? 1))
                    : null,
                'notes' => $data['notes'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            $this->syncPackages($service, $data['package_ids'] ?? []);
            $this->attachmentService->storeFor($service, $request->file('attachments', []), $request->user());
        });

        return redirect()
            ->route('admin.master-data.services.index')
            ->with('status', 'Service created successfully.');
    }

    public function edit(Service $service): View
    {
        return view('admin.master-data.services.form', [
            'service' => $service->loadCount(['packages', 'assignments'])->load('attachments'),
            'isEditing' => true,
            'packages' => Package::query()->orderBy('name')->get(['id', 'name', 'code']),
            'selectedPackageIds' => $service->packages()->pluck('packages.id')->all(),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();
        $personInputMode = $data['person_input_mode'] ?? Service::PERSON_MODE_FIXED;

        DB::transaction(function () use ($request, $service, $data, $personInputMode) {
            $service->update([
                'name' => $data['name'],
                'code' => $data['code'] ?: null,
                'standard_rate_minor' => Money::toMinor($data['standard_rate']),
                'uses_persons' => $personInputMode !== Service::PERSON_MODE_NONE,
                'person_input_mode' => $personInputMode,
                'default_persons' => $personInputMode === Service::PERSON_MODE_FIXED
                    ? max(1, (int) ($data['default_persons'] ?? 1))
                    : null,
                'notes' => $data['notes'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            $this->syncPackages($service, $data['package_ids'] ?? []);
            $this->attachmentService->storeFor($service, $request->file('attachments', []), $request->user());
        });

        return redirect()
            ->route('admin.master-data.services.edit', $service)
            ->with('status', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        if ($service->functionServiceLines()->exists()) {
            return redirect()
                ->route('admin.master-data.services.index')
                ->with('error', 'This service is already used in Function Entry records and cannot be deleted.');
        }

        if ($service->assignments()->exists() || $service->packages()->exists()) {
            return redirect()
                ->route('admin.master-data.services.index')
                ->with('error', 'This service is still assigned in packages or employee setup records. Remove those mappings first.');
        }

        $service->load('attachments');
        $service->attachments->each(fn (Attachment $attachment) => $this->attachmentService->delete($attachment));
        $service->delete();

        return redirect()
            ->route('admin.master-data.services.index')
            ->with('status', 'Service deleted successfully.');
    }

    public function toggleActive(Service $service): RedirectResponse
    {
        $service->update([
            'is_active' => ! $service->is_active,
        ]);

        return redirect()
            ->route('admin.master-data.services.index')
            ->with('status', $service->is_active
                ? 'Service activated successfully.'
                : 'Service deactivated successfully.');
    }

    public function preview(Request $request, Service $service, Attachment $attachment)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $attachment = $this->resolveAttachment($service, $attachment);
        abort_unless($attachment->canPreviewInline(), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->storage_path,
            $attachment->original_name,
            ['Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"']
        );
    }

    public function download(Request $request, Service $service, Attachment $attachment)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $attachment = $this->resolveAttachment($service, $attachment);

        return Storage::disk($attachment->disk)->download($attachment->storage_path, $attachment->original_name);
    }

    public function destroyAttachment(Request $request, Service $service, Attachment $attachment): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $attachment = $this->resolveAttachment($service, $attachment);
        $this->attachmentService->delete($attachment);

        return back()->with('status', 'Service attachment removed.');
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

    private function resolveAttachment(Service $service, Attachment $attachment): Attachment
    {
        return Attachment::query()
            ->whereKey($attachment->getKey())
            ->where('attachable_type', Service::class)
            ->where('attachable_id', $service->getKey())
            ->firstOrFail();
    }
}
