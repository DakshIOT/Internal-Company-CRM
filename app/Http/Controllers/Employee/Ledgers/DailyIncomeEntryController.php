<?php

namespace App\Http\Controllers\Employee\Ledgers;

use App\Exports\Reports\WorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Ledgers\DailyIncomeEntryRequest;
use App\Models\Attachment;
use App\Models\DailyIncomeEntry;
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

class DailyIncomeEntryController extends Controller
{
    public function __construct(
        private LedgerWorkspaceTotalsService $totalsService,
        private AttachmentService $attachmentService,
        private EmployeeRegisterExportService $exportService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DailyIncomeEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->firstOrFail();

        $date = $request->string('entry_date')->value();
        $orderedQuery = $this->indexQuery($request)->withCount('attachments')->orderByDesc('entry_date')->orderByDesc('id');
        $printMode = $request->boolean('print');
        $entries = $printMode
            ? $orderedQuery->get()
            : $orderedQuery->paginate(50)->withQueryString();

        return view('employee.ledgers.daily-income.index', [
            'currentVenue' => $venue,
            'entries' => $entries,
            'filters' => $request->only(['search', 'entry_date']),
            'isPrint' => $printMode,
            'workspaceTotals' => $this->totalsService->forEmployeeVenue(DailyIncomeEntry::class, $user, $venueId, $date ?? null),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', DailyIncomeEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->firstOrFail();
        $query = $this->indexQuery($request);

        $entries = (clone $query)
            ->with([
                'attachments' => fn ($builder) => $builder
                    ->select(['id', 'attachable_id', 'attachable_type', 'original_name', 'mime_type', 'disk', 'storage_path'])
                    ->orderBy('id'),
            ])
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
                'Daily Income',
                'employee.daily-income.attachments.download',
                'dailyIncome'
            )),
            'daily-income-register-export.xlsx'
        );
    }

    public function printDate(Request $request, string $entryDate): View
    {
        $this->authorize('viewAny', DailyIncomeEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->firstOrFail();

        try {
            $printDate = Carbon::createFromFormat('Y-m-d', $entryDate)->toDateString();
        } catch (\Throwable) {
            abort(404);
        }

        $entries = DailyIncomeEntry::query()
            ->forWorkspace($user, $venueId)
            ->whereDate('entry_date', $printDate)
            ->with(['attachments', 'venue'])
            ->withCount('attachments')
            ->orderBy('id')
            ->get();

        abort_if($entries->isEmpty(), 404);

        return view('ledgers.print-date', [
            'backRoute' => route('employee.daily-income.index', ['entry_date' => $printDate]),
            'currentVenue' => $venue,
            'downloadRoute' => 'employee.daily-income.attachments.download',
            'entries' => $entries,
            'moduleLabel' => 'Daily Income',
            'previewRoute' => 'employee.daily-income.attachments.preview',
            'printDate' => Carbon::parse($printDate),
            'routeKey' => 'dailyIncome',
            'showVenue' => false,
            'showVendor' => false,
            'totals' => [
                'entry_count' => $entries->count(),
                'amount_minor' => (int) $entries->sum('amount_minor'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', DailyIncomeEntry::class);

        $venueId = $this->selectedVenueId($request);

        return view('employee.ledgers.daily-income.create', [
            'currentVenue' => $request->user()->venues()->whereKey($venueId)->firstOrFail(),
            'entry' => new DailyIncomeEntry(['entry_date' => now()->toDateString()]),
            'workspaceTotals' => $this->totalsService->forEmployeeVenue(DailyIncomeEntry::class, $request->user(), $venueId),
        ]);
    }

    public function store(DailyIncomeEntryRequest $request): RedirectResponse
    {
        $this->authorize('create', DailyIncomeEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $data = $request->validated();

        $entry = DB::transaction(function () use ($user, $venueId, $data, $request) {
            $entry = DailyIncomeEntry::query()->create([
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
            ->route('employee.daily-income.index')
            ->with('status', 'Daily income entry created.');
    }

    public function show(Request $request, DailyIncomeEntry $dailyIncome): View
    {
        return $this->edit($request, $dailyIncome);
    }

    public function edit(Request $request, DailyIncomeEntry $dailyIncome): View
    {
        $this->authorizeEntry($request, $dailyIncome, 'view');
        $dailyIncome->load(['attachments', 'venue']);

        return view('employee.ledgers.daily-income.edit', [
            'currentVenue' => $dailyIncome->venue,
            'entry' => $dailyIncome,
            'workspaceTotals' => $this->totalsService->forEmployeeVenue(
                DailyIncomeEntry::class,
                $request->user(),
                $this->selectedVenueId($request),
                optional($dailyIncome->entry_date)->toDateString()
            ),
        ]);
    }

    public function update(DailyIncomeEntryRequest $request, DailyIncomeEntry $dailyIncome): RedirectResponse
    {
        $this->authorizeEntry($request, $dailyIncome, 'update');

        $data = $request->validated();

        DB::transaction(function () use ($request, $dailyIncome, $data) {
            $dailyIncome->update([
                'entry_date' => $data['entry_date'],
                'name' => $data['name'],
                'amount_minor' => Money::toMinor($data['amount']),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->attachmentService->storeFor($dailyIncome, $request->file('attachments', []), $request->user());
        });

        return redirect()
            ->route('employee.daily-income.index')
            ->with('status', 'Daily income entry updated.');
    }

    public function destroy(Request $request, DailyIncomeEntry $dailyIncome): RedirectResponse
    {
        $this->authorizeEntry($request, $dailyIncome, 'delete');

        DB::transaction(function () use ($dailyIncome) {
            $dailyIncome->load('attachments');
            $dailyIncome->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));
            $dailyIncome->delete();
        });

        return redirect()
            ->route('employee.daily-income.index')
            ->with('status', 'Daily income entry removed.');
    }

    public function preview(Request $request, DailyIncomeEntry $dailyIncome, Attachment $attachment)
    {
        $this->authorizeEntry($request, $dailyIncome, 'view');
        $attachment = $this->resolveAttachment($dailyIncome, $attachment);

        abort_unless($attachment->canPreviewInline(), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->storage_path,
            $attachment->original_name,
            ['Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"']
        );
    }

    public function download(Request $request, DailyIncomeEntry $dailyIncome, Attachment $attachment)
    {
        $this->authorizeEntry($request, $dailyIncome, 'view');
        $attachment = $this->resolveAttachment($dailyIncome, $attachment);

        return Storage::disk($attachment->disk)->download($attachment->storage_path, $attachment->original_name);
    }

    public function destroyAttachment(Request $request, DailyIncomeEntry $dailyIncome, Attachment $attachment): RedirectResponse
    {
        $this->authorizeEntry($request, $dailyIncome, 'update');
        $attachment = $this->resolveAttachment($dailyIncome, $attachment);

        $this->attachmentService->delete($attachment);

        return back()->with('status', 'Attachment removed.');
    }

    private function authorizeEntry(Request $request, DailyIncomeEntry $entry, string $ability): void
    {
        $this->authorize($ability, $entry);
        abort_unless((int) $entry->venue_id === $this->selectedVenueId($request), 404);
    }

    private function resolveAttachment(DailyIncomeEntry $entry, Attachment $attachment): Attachment
    {
        return Attachment::query()
            ->whereKey($attachment->getKey())
            ->where('attachable_type', DailyIncomeEntry::class)
            ->where('attachable_id', $entry->getKey())
            ->firstOrFail();
    }

    private function indexQuery(Request $request): Builder
    {
        $query = DailyIncomeEntry::query()
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
