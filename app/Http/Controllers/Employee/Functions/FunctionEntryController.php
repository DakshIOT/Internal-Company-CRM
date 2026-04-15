<?php

namespace App\Http\Controllers\Employee\Functions;

use App\Exports\Reports\WorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Functions\FunctionEntryRequest;
use App\Models\FunctionEntry;
use App\Models\Package;
use App\Models\PrintSetting;
use App\Services\Exports\EmployeeRegisterExportService;
use App\Services\Files\AttachmentService;
use App\Services\Functions\FunctionPackageAvailabilitySyncService;
use App\Services\Functions\FunctionEntryTotalsService;
use App\Services\Functions\FunctionEntryWorkspaceTotalsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FunctionEntryController extends Controller
{
    public function __construct(
        private FunctionEntryTotalsService $totalsService,
        private FunctionEntryWorkspaceTotalsService $workspaceTotalsService,
        private AttachmentService $attachmentService,
        private EmployeeRegisterExportService $exportService,
        private FunctionPackageAvailabilitySyncService $availabilitySyncService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FunctionEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->firstOrFail();

        $orderedQuery = $this->indexQuery($request)
            ->withCount(['packages', 'extraCharges', 'installments', 'discounts', 'attachments'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id');
        $printMode = $request->boolean('print');
        $entries = $printMode
            ? $orderedQuery->get()
            : $orderedQuery->paginate(50)->withQueryString();

        return view('employee.functions.index', [
            'currentVenue' => $venue,
            'entries' => $entries,
            'filters' => $request->only(['search', 'entry_date']),
            'isPrint' => $printMode,
            'workspaceTotals' => $this->workspaceTotalsService->forUserVenue($user, $venueId),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', FunctionEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->firstOrFail();
        $query = $this->indexQuery($request);

        $entries = (clone $query)
            ->with([
                'attachments' => fn ($builder) => $builder
                    ->select(['id', 'attachable_id', 'attachable_type', 'original_name', 'mime_type', 'disk', 'storage_path'])
                    ->orderBy('id'),
                'packages.serviceLines.service.attachments' => fn ($builder) => $builder
                    ->select(['id', 'attachable_id', 'attachable_type', 'original_name', 'mime_type', 'disk', 'storage_path'])
                    ->orderBy('id'),
            ])
            ->withCount(['packages', 'extraCharges', 'installments', 'discounts', 'attachments'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        $summary = $this->exportSummary(clone $query);
        $dateTotals = $this->exportDateTotals(clone $query);

        return Excel::download(
            new WorkbookExport($this->exportService->functionSheets(
                $user,
                $venue,
                $request->only(['search', 'entry_date']),
                $entries,
                $summary,
                $dateTotals
            )),
            'function-register-export.xlsx'
        );
    }

    public function printDate(Request $request, string $entryDate): View
    {
        $this->authorize('viewAny', FunctionEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->firstOrFail();

        try {
            $printDate = Carbon::createFromFormat('Y-m-d', $entryDate)->toDateString();
        } catch (\Throwable) {
            abort(404);
        }

        $entries = FunctionEntry::query()
            ->forWorkspace($user, $venueId)
            ->whereDate('entry_date', $printDate)
            ->with([
                'venue',
                'attachments',
                'packages.package',
                'packages.serviceLines.service.attachments',
                'extraCharges.attachments',
                'installments.attachments',
                'discounts.attachments',
            ])
            ->withCount(['attachments', 'packages', 'extraCharges', 'installments', 'discounts'])
            ->orderBy('id')
            ->get();

        $entries->each(fn (FunctionEntry $entry) => $this->availabilitySyncService->syncEntry($entry, $user));
        $entries->load([
            'packages.package',
            'packages.serviceLines.service.attachments',
        ]);

        abort_if($entries->isEmpty(), 404);

        $dayTotals = [
            'entry_count' => $entries->count(),
            'package_total_minor' => (int) $entries->sum('package_total_minor'),
            'extra_charge_total_minor' => (int) $entries->sum('extra_charge_total_minor'),
            'discount_total_minor' => (int) $entries->sum('discount_total_minor'),
            'function_total_minor' => (int) $entries->sum('function_total_minor'),
            'paid_total_minor' => (int) $entries->sum('paid_total_minor'),
            'pending_total_minor' => (int) $entries->sum('pending_total_minor'),
            'frozen_fund_minor' => (int) $entries->sum('frozen_fund_minor'),
            'net_total_after_frozen_fund_minor' => (int) $entries->sum('net_total_after_frozen_fund_minor'),
        ];

        return view('employee.functions.print-date', [
            'currentVenue' => $venue,
            'dayTotals' => $dayTotals,
            'entries' => $entries,
            'printDate' => Carbon::parse($printDate),
            'printSettings' => PrintSetting::current(),
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
            ->route('employee.functions.index')
            ->with('status', 'Function entry created. Open it from the list to continue in the action menu.');
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
            'packages.serviceLines.service.attachments',
            'extraCharges.attachments',
            'installments.attachments',
            'discounts.attachments',
        ]);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $this->availabilitySyncService->syncEntry($functionEntry, $user);
        $functionEntry->load([
            'packages.serviceLines.service.attachments',
        ]);
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
            ->route('employee.functions.index')
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

    private function indexQuery(Request $request): Builder
    {
        $query = FunctionEntry::query()
            ->forWorkspace($request->user(), $this->selectedVenueId($request));

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

        return $query;
    }

    private function exportSummary(Builder $query): array
    {
        $totals = $query->selectRaw('COUNT(*) as entry_count')
            ->selectRaw('COALESCE(SUM(function_total_minor), 0) as function_total_minor')
            ->selectRaw('COALESCE(SUM(paid_total_minor), 0) as paid_total_minor')
            ->selectRaw('COALESCE(SUM(pending_total_minor), 0) as pending_total_minor')
            ->selectRaw('COALESCE(SUM(frozen_fund_minor), 0) as frozen_fund_minor')
            ->selectRaw('COALESCE(SUM(net_total_after_frozen_fund_minor), 0) as net_total_after_frozen_fund_minor')
            ->first();

        return [
            'entry_count' => (int) ($totals->entry_count ?? 0),
            'function_total_minor' => (int) ($totals->function_total_minor ?? 0),
            'paid_total_minor' => (int) ($totals->paid_total_minor ?? 0),
            'pending_total_minor' => (int) ($totals->pending_total_minor ?? 0),
            'frozen_fund_minor' => (int) ($totals->frozen_fund_minor ?? 0),
            'net_total_after_frozen_fund_minor' => (int) ($totals->net_total_after_frozen_fund_minor ?? 0),
        ];
    }

    private function exportDateTotals(Builder $query)
    {
        return $query
            ->selectRaw('entry_date, COUNT(*) as entry_count')
            ->selectRaw('COALESCE(SUM(function_total_minor), 0) as function_total_minor')
            ->selectRaw('COALESCE(SUM(paid_total_minor), 0) as paid_total_minor')
            ->selectRaw('COALESCE(SUM(pending_total_minor), 0) as pending_total_minor')
            ->selectRaw('COALESCE(SUM(frozen_fund_minor), 0) as frozen_fund_minor')
            ->selectRaw('COALESCE(SUM(net_total_after_frozen_fund_minor), 0) as net_total_after_frozen_fund_minor')
            ->groupBy('entry_date')
            ->orderByDesc('entry_date')
            ->get()
            ->map(function ($row) {
                return [
                    'entry_date' => $row->entry_date instanceof Carbon
                        ? $row->entry_date->toDateString()
                        : Carbon::parse($row->entry_date)->toDateString(),
                    'entry_count' => (int) $row->entry_count,
                    'function_total_minor' => (int) $row->function_total_minor,
                    'paid_total_minor' => (int) $row->paid_total_minor,
                    'pending_total_minor' => (int) $row->pending_total_minor,
                    'frozen_fund_minor' => (int) $row->frozen_fund_minor,
                    'net_total_after_frozen_fund_minor' => (int) $row->net_total_after_frozen_fund_minor,
                ];
            });
    }

    private function selectedVenueId(Request $request): int
    {
        return (int) $request->session()->get('selected_venue_id');
    }
}
