<?php

namespace App\Http\Controllers\Admin\Ledgers;

use App\Exports\Reports\WorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ledgers\AdminIncomeEntryRequest;
use App\Models\AdminIncomeEntry;
use App\Models\Attachment;
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

class AdminIncomeEntryController extends Controller
{
    public function __construct(
        private LedgerWorkspaceTotalsService $totalsService,
        private AttachmentService $attachmentService,
        private EmployeeRegisterExportService $exportService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AdminIncomeEntry::class);

        $date = $request->string('entry_date')->value();
        $orderedQuery = $this->indexQuery($request)->withCount('attachments')->orderByDesc('entry_date')->orderByDesc('id');
        $printMode = $request->boolean('print');
        $entries = $printMode
            ? $orderedQuery->get()
            : $orderedQuery->paginate(50)->withQueryString();

        return view('admin.ledgers.admin-income.index', [
            'entries' => $entries,
            'filters' => $request->only(['search', 'entry_date']),
            'isPrint' => $printMode,
            'workspaceTotals' => $this->totalsService->forAdmin(AdminIncomeEntry::class, $date ?? null),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', AdminIncomeEntry::class);

        $query = $this->indexQuery($request);
        $entries = (clone $query)
            ->with([
                'user:id,name,role',
                'attachments' => fn ($builder) => $builder
                    ->select(['id', 'attachable_id', 'attachable_type', 'original_name', 'mime_type', 'disk', 'storage_path'])
                    ->orderBy('id'),
            ])
            ->withCount('attachments')
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        return Excel::download(
            new WorkbookExport($this->exportService->adminAmountSheets(
                $request->user(),
                $request->only(['search', 'entry_date']),
                $entries,
                $this->exportSummary(clone $query),
                $this->exportDateTotals(clone $query),
                'Admin Income',
                'admin.admin-income.attachments.download',
                'adminIncome',
            )),
            'admin-income-register-export.xlsx'
        );
    }

    public function printDate(Request $request, string $entryDate): View
    {
        $this->authorize('viewAny', AdminIncomeEntry::class);

        try {
            $printDate = Carbon::createFromFormat('Y-m-d', $entryDate)->toDateString();
        } catch (\Throwable) {
            abort(404);
        }

        $entries = AdminIncomeEntry::query()
            ->whereDate('entry_date', $printDate)
            ->with(['attachments', 'user'])
            ->withCount('attachments')
            ->orderBy('id')
            ->get();

        abort_if($entries->isEmpty(), 404);

        return view('ledgers.print-date', [
            'backRoute' => route('admin.admin-income.index', ['entry_date' => $printDate]),
            'currentVenue' => null,
            'downloadRoute' => 'admin.admin-income.attachments.download',
            'entries' => $entries,
            'moduleLabel' => 'Admin Income',
            'previewRoute' => 'admin.admin-income.attachments.preview',
            'printDate' => Carbon::parse($printDate),
            'routeKey' => 'adminIncome',
            'showVenue' => false,
            'showVendor' => false,
            'totals' => [
                'entry_count' => $entries->count(),
                'amount_minor' => (int) $entries->sum('amount_minor'),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AdminIncomeEntry::class);

        return view('admin.ledgers.admin-income.create', [
            'entry' => new AdminIncomeEntry(['entry_date' => now()->toDateString()]),
            'workspaceTotals' => $this->totalsService->forAdmin(AdminIncomeEntry::class),
        ]);
    }

    public function store(AdminIncomeEntryRequest $request): RedirectResponse
    {
        $this->authorize('create', AdminIncomeEntry::class);

        $data = $request->validated();
        $entry = DB::transaction(function () use ($request, $data) {
            $entry = AdminIncomeEntry::query()->create([
                'user_id' => $request->user()->getKey(),
                'entry_date' => $data['entry_date'],
                'name' => $data['name'],
                'amount_minor' => Money::toMinor($data['amount']),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->attachmentService->storeFor($entry, $request->file('attachments', []), $request->user());

            return $entry;
        });

        return redirect()
            ->route('admin.admin-income.edit', $entry)
            ->with('status', 'Admin income entry created.');
    }

    public function show(Request $request, AdminIncomeEntry $adminIncome): View
    {
        return $this->edit($request, $adminIncome);
    }

    public function edit(Request $request, AdminIncomeEntry $adminIncome): View
    {
        $this->authorize('view', $adminIncome);
        $adminIncome->load('attachments');

        return view('admin.ledgers.admin-income.edit', [
            'entry' => $adminIncome,
            'workspaceTotals' => $this->totalsService->forAdmin(AdminIncomeEntry::class, optional($adminIncome->entry_date)->toDateString()),
        ]);
    }

    public function update(AdminIncomeEntryRequest $request, AdminIncomeEntry $adminIncome): RedirectResponse
    {
        $this->authorize('update', $adminIncome);

        $data = $request->validated();

        DB::transaction(function () use ($request, $adminIncome, $data) {
            $adminIncome->update([
                'entry_date' => $data['entry_date'],
                'name' => $data['name'],
                'amount_minor' => Money::toMinor($data['amount']),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->attachmentService->storeFor($adminIncome, $request->file('attachments', []), $request->user());
        });

        return redirect()
            ->route('admin.admin-income.edit', $adminIncome)
            ->with('status', 'Admin income entry updated.');
    }

    public function destroy(AdminIncomeEntry $adminIncome): RedirectResponse
    {
        $this->authorize('delete', $adminIncome);

        DB::transaction(function () use ($adminIncome) {
            $adminIncome->load('attachments');
            $adminIncome->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));
            $adminIncome->delete();
        });

        return redirect()
            ->route('admin.admin-income.index')
            ->with('status', 'Admin income entry removed.');
    }

    public function preview(AdminIncomeEntry $adminIncome, Attachment $attachment)
    {
        $this->authorize('view', $adminIncome);
        $attachment = $this->resolveAttachment($adminIncome, $attachment);

        abort_unless($attachment->canPreviewInline(), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->storage_path,
            $attachment->original_name,
            ['Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"']
        );
    }

    public function download(AdminIncomeEntry $adminIncome, Attachment $attachment)
    {
        $this->authorize('view', $adminIncome);
        $attachment = $this->resolveAttachment($adminIncome, $attachment);

        return Storage::disk($attachment->disk)->download($attachment->storage_path, $attachment->original_name);
    }

    public function destroyAttachment(AdminIncomeEntry $adminIncome, Attachment $attachment): RedirectResponse
    {
        $this->authorize('update', $adminIncome);
        $attachment = $this->resolveAttachment($adminIncome, $attachment);

        $this->attachmentService->delete($attachment);

        return back()->with('status', 'Attachment removed.');
    }

    private function resolveAttachment(AdminIncomeEntry $entry, Attachment $attachment): Attachment
    {
        return Attachment::query()
            ->whereKey($attachment->getKey())
            ->where('attachable_type', AdminIncomeEntry::class)
            ->where('attachable_id', $entry->getKey())
            ->firstOrFail();
    }

    private function indexQuery(Request $request): Builder
    {
        $query = AdminIncomeEntry::query();

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
}
