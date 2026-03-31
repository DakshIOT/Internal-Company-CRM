<?php

namespace App\Http\Controllers\Employee\Functions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Functions\FunctionEntryRequest;
use App\Models\FunctionEntry;
use App\Models\Package;
use App\Services\Files\AttachmentService;
use App\Services\Functions\FunctionEntryTotalsService;
use App\Services\Functions\FunctionEntryWorkspaceTotalsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FunctionEntryController extends Controller
{
    public function __construct(
        private FunctionEntryTotalsService $totalsService,
        private FunctionEntryWorkspaceTotalsService $workspaceTotalsService,
        private AttachmentService $attachmentService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FunctionEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->firstOrFail();

        $query = FunctionEntry::query()
            ->forWorkspace($user, $venueId)
            ->withCount(['packages', 'extraCharges', 'installments', 'discounts', 'attachments']);

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($date = $request->string('entry_date')->value()) {
            $query->whereDate('entry_date', $date);
        }

        return view('employee.functions.index', [
            'currentVenue' => $venue,
            'entries' => $query->orderByDesc('entry_date')->orderByDesc('id')->paginate(12)->withQueryString(),
            'filters' => $request->only(['search', 'entry_date']),
            'workspaceTotals' => $this->workspaceTotalsService->forUserVenue($user, $venueId),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FunctionEntry::class);

        return view('employee.functions.create', [
            'currentVenue' => $request->user()->venues()->whereKey($this->selectedVenueId($request))->firstOrFail(),
            'functionEntry' => new FunctionEntry([
                'entry_date' => now()->toDateString(),
            ]),
        ]);
    }

    public function store(FunctionEntryRequest $request): RedirectResponse
    {
        $this->authorize('create', FunctionEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $data = $request->validated();

        $functionEntry = DB::transaction(function () use ($user, $venueId, $data, $request) {
            $entry = FunctionEntry::create([
                'user_id' => $user->getKey(),
                'venue_id' => $venueId,
                'entry_date' => $data['entry_date'],
                'name' => $data['name'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->attachmentService->storeFor($entry, $request->file('attachments', []), $user);

            return $this->totalsService->recalculate($entry);
        });

        return redirect()
            ->route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'packages'])
            ->with('status', 'Function entry created. Continue in the action center.');
    }

    public function show(Request $request, FunctionEntry $functionEntry): View
    {
        return $this->edit($request, $functionEntry);
    }

    public function edit(Request $request, FunctionEntry $functionEntry): View
    {
        $this->authorizeFunctionEntry($request, $functionEntry, 'view');

        $functionEntry->load([
            'venue',
            'attachments',
            'packages.serviceLines.service',
            'extraCharges.attachments',
            'installments.attachments',
            'discounts.attachments',
        ]);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $assignedPackageIds = $user->packageAssignments()
            ->where('venue_id', $venueId)
            ->pluck('package_id');
        $assignedServiceIds = $user->serviceAssignments()
            ->where('venue_id', $venueId)
            ->pluck('service_id');
        $selectedPackageIds = $functionEntry->packages->pluck('package_id');

        return view('employee.functions.edit', [
            'availablePackages' => Package::query()
                ->active()
                ->whereIn('id', $assignedPackageIds)
                ->whereNotIn('id', $selectedPackageIds)
                ->whereHas('services', fn ($query) => $query->whereIn('services.id', $assignedServiceIds))
                ->orderBy('name')
                ->get(),
            'currentVenue' => $functionEntry->venue,
            'functionEntry' => $functionEntry,
            'modeOptions' => \App\Support\TransactionMode::options(),
            'selectedTab' => $request->query('tab'),
            'workspaceTotals' => $this->workspaceTotalsService->forUserVenue(
                $user,
                $venueId,
                $functionEntry->entry_date?->toDateString()
            ),
        ]);
    }

    public function update(FunctionEntryRequest $request, FunctionEntry $functionEntry): RedirectResponse
    {
        $this->authorizeFunctionEntry($request, $functionEntry, 'update');

        $data = $request->validated();

        DB::transaction(function () use ($request, $functionEntry, $data) {
            $functionEntry->update([
                'entry_date' => $data['entry_date'],
                'name' => $data['name'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->attachmentService->storeFor($functionEntry, $request->file('attachments', []), $request->user());
            $this->totalsService->recalculate($functionEntry);
        });

        return redirect()
            ->route('employee.functions.edit', ['functionEntry' => $functionEntry, 'tab' => 'packages'])
            ->with('status', 'Function entry details updated.');
    }

    public function destroy(Request $request, FunctionEntry $functionEntry): RedirectResponse
    {
        $this->authorizeFunctionEntry($request, $functionEntry, 'delete');

        DB::transaction(function () use ($functionEntry) {
            $functionEntry->load([
                'attachments',
                'extraCharges.attachments',
                'installments.attachments',
                'discounts.attachments',
            ]);

            $functionEntry->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));
            $functionEntry->extraCharges->flatMap->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));
            $functionEntry->installments->flatMap->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));
            $functionEntry->discounts->flatMap->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));

            $functionEntry->delete();
        });

        return redirect()
            ->route('employee.functions.index')
            ->with('status', 'Function entry removed.');
    }

    private function authorizeFunctionEntry(Request $request, FunctionEntry $functionEntry, string $ability): void
    {
        $this->authorize($ability, $functionEntry);
        abort_unless((int) $functionEntry->venue_id === $this->selectedVenueId($request), 404);
    }

    private function selectedVenueId(Request $request): int
    {
        return (int) $request->session()->get('selected_venue_id');
    }
}
