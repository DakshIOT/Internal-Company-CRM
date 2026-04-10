<?php

namespace App\Http\Controllers\Employee\Ledgers;

use App\Exports\Reports\WorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Ledgers\VendorEntryRequest;
use App\Models\Attachment;
use App\Models\VendorEntry;
use App\Models\VenueVendor;
use App\Services\Exports\EmployeeRegisterExportService;
use App\Services\Files\AttachmentService;
use App\Services\Ledgers\VendorEntryWorkspaceTotalsService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VendorEntryController extends Controller
{
    public function __construct(
        private VendorEntryWorkspaceTotalsService $totalsService,
        private AttachmentService $attachmentService,
        private EmployeeRegisterExportService $exportService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', VendorEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->with('vendors')->firstOrFail();

        $date = $request->string('entry_date')->value();
        $orderedQuery = $this->indexQuery($request)
            ->with('venueVendor')
            ->withCount('attachments')
            ->orderByDesc('entry_date')
            ->orderByDesc('id');
        $printMode = $request->boolean('print');
        $entries = $printMode
            ? $orderedQuery->get()
            : $orderedQuery->paginate(50)->withQueryString();

        return view('employee.ledgers.vendor-entries.index', [
            'currentVenue' => $venue,
            'entries' => $entries,
            'filters' => $request->only(['search', 'entry_date', 'venue_vendor_id']),
            'isPrint' => $printMode,
            'vendorTotals' => $this->totalsService->vendorTotalsForUserVenue($user, $venue),
            'workspaceTotals' => $this->totalsService->forEmployeeVenue(VendorEntry::class, $user, $venueId, $date ?? null),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', VendorEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->with('vendors')->firstOrFail();
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
        $vendorTotals = $this->totalsService->vendorTotalsFromQuery(clone $query);

        return Excel::download(
            new WorkbookExport($this->exportService->amountSheets(
                $user,
                $venue,
                $request->only(['search', 'entry_date', 'venue_vendor_id']),
                $entries,
                $summary,
                $dateTotals,
                'Vendor Entry',
                'employee.vendor-entries.attachments.download',
                'vendorEntry',
                true,
                $vendorTotals
            )),
            'vendor-register-export.xlsx'
        );
    }

    public function printDate(Request $request, string $entryDate): View
    {
        $this->authorize('viewAny', VendorEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $venue = $user->venues()->whereKey($venueId)->firstOrFail();

        try {
            $printDate = Carbon::createFromFormat('Y-m-d', $entryDate)->toDateString();
        } catch (\Throwable) {
            abort(404);
        }

        $entries = VendorEntry::query()
            ->forWorkspace($user, $venueId)
            ->whereDate('entry_date', $printDate)
            ->with(['attachments', 'venue', 'venueVendor'])
            ->withCount('attachments')
            ->orderBy('id')
            ->get();

        abort_if($entries->isEmpty(), 404);

        return view('ledgers.print-date', [
            'backRoute' => route('employee.vendor-entries.index', ['entry_date' => $printDate]),
            'currentVenue' => $venue,
            'downloadRoute' => 'employee.vendor-entries.attachments.download',
            'entries' => $entries,
            'moduleLabel' => 'Vendor Entry',
            'previewRoute' => 'employee.vendor-entries.attachments.preview',
            'printDate' => Carbon::parse($printDate),
            'routeKey' => 'vendorEntry',
            'showVenue' => false,
            'showVendor' => true,
            'totals' => [
                'entry_count' => $entries->count(),
                'amount_minor' => (int) $entries->sum('amount_minor'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', VendorEntry::class);

        $venueId = $this->selectedVenueId($request);
        $venue = $request->user()
            ->venues()
            ->whereKey($venueId)
            ->with('vendors')
            ->firstOrFail();

        return view('employee.ledgers.vendor-entries.create', [
            'currentVenue' => $venue,
            'entry' => new VendorEntry(['entry_date' => now()->toDateString()]),
            'vendorOptions' => $venue->vendors,
            'workspaceTotals' => $this->totalsService->forEmployeeVenue(VendorEntry::class, $request->user(), $venueId),
        ]);
    }

    public function store(VendorEntryRequest $request): RedirectResponse
    {
        $this->authorize('create', VendorEntry::class);

        $user = $request->user();
        $venueId = $this->selectedVenueId($request);
        $data = $request->validated();
        $vendor = $this->resolveVendor($venueId, (int) $data['venue_vendor_id']);

        $entry = DB::transaction(function () use ($user, $venueId, $data, $vendor, $request) {
            $entry = VendorEntry::query()->create([
                'user_id' => $user->getKey(),
                'venue_id' => $venueId,
                'venue_vendor_id' => $vendor->getKey(),
                'vendor_name_snapshot' => $vendor->name,
                'entry_date' => $data['entry_date'],
                'name' => $data['name'],
                'amount_minor' => Money::toMinor($data['amount']),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->attachmentService->storeFor($entry, $request->file('attachments', []), $user);

            return $entry;
        });

        return redirect()
            ->route('employee.vendor-entries.index')
            ->with('status', 'Vendor entry created.');
    }

    public function show(Request $request, VendorEntry $vendorEntry): View
    {
        return $this->edit($request, $vendorEntry);
    }

    public function edit(Request $request, VendorEntry $vendorEntry): View
    {
        $this->authorizeEntry($request, $vendorEntry, 'view');
        $vendorEntry->load(['attachments', 'venue', 'venueVendor']);
        $venue = $vendorEntry->venue->load('vendors');

        return view('employee.ledgers.vendor-entries.edit', [
            'currentVenue' => $venue,
            'entry' => $vendorEntry,
            'vendorOptions' => $venue->vendors,
            'workspaceTotals' => $this->totalsService->forEmployeeVenue(
                VendorEntry::class,
                $request->user(),
                $this->selectedVenueId($request),
                optional($vendorEntry->entry_date)->toDateString()
            ),
        ]);
    }

    public function updateVendorName(Request $request, VenueVendor $venueVendor): RedirectResponse
    {
        $this->authorize('create', VendorEntry::class);

        $venueId = $this->selectedVenueId($request);
        abort_unless((int) $venueVendor->venue_id === $venueId, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        DB::transaction(function () use ($venueVendor, $data) {
            $venueVendor->update([
                'name' => trim($data['name']),
            ]);

            VendorEntry::query()
                ->where('venue_vendor_id', $venueVendor->getKey())
                ->update([
                    'vendor_name_snapshot' => trim($data['name']),
                ]);
        });

        return back()->with('status', 'Vendor name updated.');
    }

    public function update(VendorEntryRequest $request, VendorEntry $vendorEntry): RedirectResponse
    {
        $this->authorizeEntry($request, $vendorEntry, 'update');

        $data = $request->validated();
        $vendor = $this->resolveVendor($this->selectedVenueId($request), (int) $data['venue_vendor_id']);

        DB::transaction(function () use ($request, $vendorEntry, $vendor, $data) {
            $vendorEntry->update([
                'venue_vendor_id' => $vendor->getKey(),
                'vendor_name_snapshot' => $vendor->name,
                'entry_date' => $data['entry_date'],
                'name' => $data['name'],
                'amount_minor' => Money::toMinor($data['amount']),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->attachmentService->storeFor($vendorEntry, $request->file('attachments', []), $request->user());
        });

        return redirect()
            ->route('employee.vendor-entries.index')
            ->with('status', 'Vendor entry updated.');
    }

    public function destroy(Request $request, VendorEntry $vendorEntry): RedirectResponse
    {
        $this->authorizeEntry($request, $vendorEntry, 'delete');

        DB::transaction(function () use ($vendorEntry) {
            $vendorEntry->load('attachments');
            $vendorEntry->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));
            $vendorEntry->delete();
        });

        return redirect()
            ->route('employee.vendor-entries.index')
            ->with('status', 'Vendor entry removed.');
    }

    public function preview(Request $request, VendorEntry $vendorEntry, Attachment $attachment)
    {
        $this->authorizeEntry($request, $vendorEntry, 'view');
        $attachment = $this->resolveAttachment($vendorEntry, $attachment);

        abort_unless($attachment->canPreviewInline(), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->storage_path,
            $attachment->original_name,
            ['Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"']
        );
    }

    public function download(Request $request, VendorEntry $vendorEntry, Attachment $attachment)
    {
        $this->authorizeEntry($request, $vendorEntry, 'view');
        $attachment = $this->resolveAttachment($vendorEntry, $attachment);

        return Storage::disk($attachment->disk)->download($attachment->storage_path, $attachment->original_name);
    }

    public function destroyAttachment(Request $request, VendorEntry $vendorEntry, Attachment $attachment): RedirectResponse
    {
        $this->authorizeEntry($request, $vendorEntry, 'update');
        $attachment = $this->resolveAttachment($vendorEntry, $attachment);

        $this->attachmentService->delete($attachment);

        return back()->with('status', 'Attachment removed.');
    }

    private function authorizeEntry(Request $request, VendorEntry $entry, string $ability): void
    {
        $this->authorize($ability, $entry);
        abort_unless((int) $entry->venue_id === $this->selectedVenueId($request), 404);
    }

    private function resolveAttachment(VendorEntry $entry, Attachment $attachment): Attachment
    {
        return Attachment::query()
            ->whereKey($attachment->getKey())
            ->where('attachable_type', VendorEntry::class)
            ->where('attachable_id', $entry->getKey())
            ->firstOrFail();
    }

    private function resolveVendor(int $venueId, int $vendorId): VenueVendor
    {
        $vendor = VenueVendor::query()
            ->where('venue_id', $venueId)
            ->whereKey($vendorId)
            ->first();

        if (! $vendor) {
            throw ValidationException::withMessages([
                'venue_vendor_id' => 'Select one of the configured vendors for the current venue.',
            ]);
        }

        return $vendor;
    }

    private function indexQuery(Request $request): Builder
    {
        $query = VendorEntry::query()
            ->forWorkspace($request->user(), $this->selectedVenueId($request));

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('vendor_name_snapshot', 'like', "%{$search}%")
                    ->orWhereHas('venueVendor', function ($vendorQuery) use ($search) {
                        $vendorQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($date = $request->string('entry_date')->value()) {
            $query->whereDate('entry_date', $date);
        }

        if ($vendorId = $request->integer('venue_vendor_id')) {
            $query->where('venue_vendor_id', $vendorId);
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
