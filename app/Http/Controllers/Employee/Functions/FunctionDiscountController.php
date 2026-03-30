<?php

namespace App\Http\Controllers\Employee\Functions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Functions\FunctionDiscountRequest;
use App\Models\FunctionDiscount;
use App\Models\FunctionEntry;
use App\Services\Files\AttachmentService;
use App\Services\Functions\FunctionEntryTotalsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FunctionDiscountController extends Controller
{
    public function __construct(
        private FunctionEntryTotalsService $totalsService,
        private AttachmentService $attachmentService,
    ) {
    }

    public function store(FunctionDiscountRequest $request, FunctionEntry $functionEntry): RedirectResponse
    {
        $this->authorizeEntry($request, $functionEntry);

        DB::transaction(function () use ($request, $functionEntry) {
            $record = $functionEntry->discounts()->create([
                'entry_date' => $request->validated('entry_date'),
                'name' => $request->validated('name'),
                'mode' => $request->validated('mode'),
                'amount_minor' => Money::toMinor($request->validated('amount')),
                'note' => $request->validated('note'),
            ]);

            $this->attachmentService->storeFor($record, $request->file('attachments', []), $request->user());
            $this->totalsService->recalculate($functionEntry);
        });

        return $this->back($functionEntry, 'discounts', 'Discount added.');
    }

    public function update(
        FunctionDiscountRequest $request,
        FunctionEntry $functionEntry,
        FunctionDiscount $functionDiscount
    ): RedirectResponse {
        $this->authorizeEntry($request, $functionEntry);
        $functionDiscount = $this->resolveRecord($functionEntry, $functionDiscount);

        DB::transaction(function () use ($request, $functionEntry, $functionDiscount) {
            $functionDiscount->update([
                'entry_date' => $request->validated('entry_date'),
                'name' => $request->validated('name'),
                'mode' => $request->validated('mode'),
                'amount_minor' => Money::toMinor($request->validated('amount')),
                'note' => $request->validated('note'),
            ]);

            $this->attachmentService->storeFor($functionDiscount, $request->file('attachments', []), $request->user());
            $this->totalsService->recalculate($functionEntry);
        });

        return $this->back($functionEntry, 'discounts', 'Discount updated.');
    }

    public function destroy(Request $request, FunctionEntry $functionEntry, FunctionDiscount $functionDiscount): RedirectResponse
    {
        $this->authorizeEntry($request, $functionEntry);
        $functionDiscount = $this->resolveRecord($functionEntry, $functionDiscount);

        DB::transaction(function () use ($functionEntry, $functionDiscount) {
            $functionDiscount->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));
            $functionDiscount->delete();
            $this->totalsService->recalculate($functionEntry);
        });

        return $this->back($functionEntry, 'discounts', 'Discount removed.');
    }

    private function authorizeEntry(Request $request, FunctionEntry $functionEntry): void
    {
        $this->authorize('update', $functionEntry);
        abort_unless((int) $functionEntry->venue_id === (int) $request->session()->get('selected_venue_id'), 404);
    }

    private function resolveRecord(FunctionEntry $functionEntry, FunctionDiscount $functionDiscount): FunctionDiscount
    {
        abort_unless((int) $functionDiscount->function_entry_id === (int) $functionEntry->id, 404);

        return $functionDiscount->loadMissing('attachments');
    }

    private function back(FunctionEntry $functionEntry, string $tab, string $status): RedirectResponse
    {
        return redirect()
            ->route('employee.functions.edit', ['functionEntry' => $functionEntry->id, 'tab' => $tab])
            ->with('status', $status);
    }
}
