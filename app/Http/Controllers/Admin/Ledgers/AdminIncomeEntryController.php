<?php

namespace App\Http\Controllers\Admin\Ledgers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ledgers\AdminIncomeEntryRequest;
use App\Models\AdminIncomeEntry;
use App\Models\Attachment;
use App\Services\Files\AttachmentService;
use App\Services\Ledgers\LedgerWorkspaceTotalsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminIncomeEntryController extends Controller
{
    public function __construct(
        private LedgerWorkspaceTotalsService $totalsService,
        private AttachmentService $attachmentService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AdminIncomeEntry::class);

        $query = AdminIncomeEntry::query()->withCount('attachments');

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

        return view('admin.ledgers.admin-income.index', [
            'entries' => $query->orderByDesc('entry_date')->orderByDesc('id')->paginate(12)->withQueryString(),
            'filters' => $request->only(['search', 'entry_date']),
            'workspaceTotals' => $this->totalsService->forAdmin(AdminIncomeEntry::class, $date ?? null),
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
}
