<?php

namespace App\Http\Controllers\Employee\Ledgers;

use App\Exports\Reports\WorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Ledgers\DailyBillingEntryRequest;
use App\Models\Attachment;
use App\Models\DailyBillingEntry;
use App\Services\Exports\EmployeeRegisterExportService;
use App\Services\Files\AttachmentService;
use App\Services\Ledgers\LedgerWorkspaceTotalsService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DailyBillingEntryController extends Controller
{
    public function __construct(
        private LedgerWorkspaceTotalsService $totalsService,
        private AttachmentService $attachmentService,
        private EmployeeRegisterExportService $exportService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DailyBillingEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->firstOrFail();

        $date = $request->string('entry_date')->value();
        $orderedQuery = $this->indexQuery($request)->withCount('attachments')->orderByDesc('entry_date')->orderByDesc('id');
        $printMode = $request->boolean('print');
        $entries = $printMode
            ? $orderedQuery->get()
            : $orderedQuery->paginate(50)->withQueryString();

        return view('employee.ledgers.daily-billing.index', [
            'currentVenue' => $venue,
            'entries' => $entries,
            'filters' => $request->only(['search', 'entry_date']),
            'isPrint' => $printMode,
            'workspaceTotals' => $this->totalsService->forEmployeeVenue(DailyBillingEntry::class, $user, $venueId, $date ?? null),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', DailyBillingEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->firstOrFail();
        $query = $this->indexQuery($request);

        $entries = (clone $query)
            ->withCount('attachments')
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        $summary = $this->exportSummary(clone $query);
        $dateTotals = $this->exportDateTotals(clone $query);

        return Excel::download(
            new WorkbookExport($this->exportService->amountSheets(
                $user,
                $venue,
                $request->only(['search', 'entry_date']),
                $entries,
                $summary,
                $dateTotals,
                'Daily Billing'
            )),
            'daily-billing-register-export.xlsx'
        );
    }

    public function create(Request $request): View
    {
        $this->authorize('create', DailyBillingEntry::class);

        $venueId = $this->selectedVenueId($request);

        return view('employee.ledgers.daily-billing.create', [
            'currentVenue' => $request->user()->venues()->whereKey($venueId)->firstOrFail(),
            'entry' => new DailyBillingEntry(['entry_date' => now()->toDateString()]),
            'workspaceTotals' => $this->totalsService->forEmployeeVenue(DailyBillingEntry::class, $request->user(), $venueId),
        ]);
    }

    public function store(DailyBillingEntryRequest $request): RedirectResponse
    {
        $this->authorize('create', DailyBillingEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $data = $request->validated();

        $entry = DB::transaction(function () use ($user, $venueId, $data, $request) {
            $entry = DailyBillingEntry::query()->create([
                'user_id' => $user->getKey(),
                'venue_id' => $venueId,
                'entry_date' => $data['entry_date'],
                'name' => $data['name'],
                'amount_minor' => Money::toMinor($data['amount']),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->attachmentService->storeFor($entry, $request->file('attachments', []), $user);

            return $entry;
        });

        return redirect()
            ->route('employee.daily-billing.index')
            ->with('status', 'Daily billing entry created.');
    }

    public function show(Request $request, DailyBillingEntry $dailyBilling): View
    {
        return $this->edit($request, $dailyBilling);
    }

    public function edit(Request $request, DailyBillingEntry $dailyBilling): View
    {
        $this->authorizeEntry($request, $dailyBilling, 'view');
        $dailyBilling->load(['attachments', 'venue']);

        return view('employee.ledgers.daily-billing.edit', [
            'currentVenue' => $dailyBilling->venue,
            'entry' => $dailyBilling,
            'workspaceTotals' => $this->totalsService->forEmployeeVenue(
                DailyBillingEntry::class,
                $request->user(),
                $this->selectedVenueId($request),
                optional($dailyBilling->entry_date)->toDateString()
            ),
        ]);
    }

    public function update(DailyBillingEntryRequest $request, DailyBillingEntry $dailyBilling): RedirectResponse
    {
        $this->authorizeEntry($request, $dailyBilling, 'update');

        $data = $request->validated();

        DB::transaction(function () use ($request, $dailyBilling, $data) {
            $dailyBilling->update([
                'entry_date' => $data['entry_date'],
                'name' => $data['name'],
                'amount_minor' => Money::toMinor($data['amount']),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->attachmentService->storeFor($dailyBilling, $request->file('attachments', []), $request->user());
        });

        return redirect()
            ->route('employee.daily-billing.index')
            ->with('status', 'Daily billing entry updated.');
    }

    public function destroy(Request $request, DailyBillingEntry $dailyBilling): RedirectResponse
    {
        $this->authorizeEntry($request, $dailyBilling, 'delete');

        DB::transaction(function () use ($dailyBilling) {
            $dailyBilling->load('attachments');
            $dailyBilling->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));
            $dailyBilling->delete();
        });

        return redirect()
            ->route('employee.daily-billing.index')
            ->with('status', 'Daily billing entry removed.');
    }

    public function preview(Request $request, DailyBillingEntry $dailyBilling, Attachment $attachment)
    {
        $this->authorizeEntry($request, $dailyBilling, 'view');
        $attachment = $this->resolveAttachment($dailyBilling, $attachment);

        abort_unless($attachment->canPreviewInline(), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->storage_path,
            $attachment->original_name,
            ['Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"']
        );
    }

    public function download(Request $request, DailyBillingEntry $dailyBilling, Attachment $attachment)
    {
        $this->authorizeEntry($request, $dailyBilling, 'view');
        $attachment = $this->resolveAttachment($dailyBilling, $attachment);

        return Storage::disk($attachment->disk)->download($attachment->storage_path, $attachment->original_name);
    }

    public function destroyAttachment(Request $request, DailyBillingEntry $dailyBilling, Attachment $attachment): RedirectResponse
    {
        $this->authorizeEntry($request, $dailyBilling, 'update');
        $attachment = $this->resolveAttachment($dailyBilling, $attachment);

        $this->attachmentService->delete($attachment);

        return back()->with('status', 'Attachment removed.');
    }

    private function authorizeEntry(Request $request, DailyBillingEntry $entry, string $ability): void
    {
        $this->authorize($ability, $entry);
        abort_unless((int) $entry->venue_id === $this->selectedVenueId($request), 404);
    }

    private function resolveAttachment(DailyBillingEntry $entry, Attachment $attachment): Attachment
    {
        return Attachment::query()
            ->whereKey($attachment->getKey())
            ->where('attachable_type', DailyBillingEntry::class)
            ->where('attachable_id', $entry->getKey())
            ->firstOrFail();
    }

    private function indexQuery(Request $request): Builder
    {
        $query = DailyBillingEntry::query()
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
        $summary = $query
            ->selectRaw('COUNT(*) as entry_count')
            ->selectRaw('COALESCE(SUM(amount_minor), 0) as amount_minor')
            ->first();

        return [
            'entry_count' => (int) ($summary->entry_count ?? 0),
            'amount_minor' => (int) ($summary->amount_minor ?? 0),
        ];
    }

    private function exportDateTotals(Builder $query)
    {
        return $query
            ->selectRaw('entry_date, COUNT(*) as entry_count, COALESCE(SUM(amount_minor), 0) as amount_minor')
            ->groupBy('entry_date')
            ->orderByDesc('entry_date')
            ->get()
            ->map(function ($row) {
                return [
                    'entry_date' => $row->entry_date instanceof Carbon
                        ? $row->entry_date->toDateString()
                        : Carbon::parse($row->entry_date)->toDateString(),
                    'entry_count' => (int) $row->entry_count,
                    'amount_minor' => (int) $row->amount_minor,
                ];
            });
    }

    private function selectedVenueId(Request $request): int
    {
        return (int) $request->session()->get('selected_venue_id');
    }
}
