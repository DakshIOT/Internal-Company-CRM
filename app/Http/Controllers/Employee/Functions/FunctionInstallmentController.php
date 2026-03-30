<?php

namespace App\Http\Controllers\Employee\Functions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Functions\FunctionInstallmentRequest;
use App\Models\FunctionEntry;
use App\Models\FunctionInstallment;
use App\Services\Files\AttachmentService;
use App\Services\Functions\FunctionEntryTotalsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FunctionInstallmentController extends Controller
{
    public function __construct(
        private FunctionEntryTotalsService $totalsService,
        private AttachmentService $attachmentService,
    ) {
    }

    public function store(FunctionInstallmentRequest $request, FunctionEntry $functionEntry): RedirectResponse
    {
        $this->authorizeEntry($request, $functionEntry);

        DB::transaction(function () use ($request, $functionEntry) {
            $record = $functionEntry->installments()->create([
                'entry_date' => $request->validated('entry_date'),
                'name' => $request->validated('name'),
                'mode' => $request->validated('mode'),
                'amount_minor' => Money::toMinor($request->validated('amount')),
                'note' => $request->validated('note'),
            ]);

            $this->attachmentService->storeFor($record, $request->file('attachments', []), $request->user());
            $this->totalsService->recalculate($functionEntry);
        });

        return $this->back($functionEntry, 'installments', 'Installment added.');
    }

    public function update(
        FunctionInstallmentRequest $request,
        FunctionEntry $functionEntry,
        FunctionInstallment $functionInstallment
    ): RedirectResponse {
        $this->authorizeEntry($request, $functionEntry);
        $functionInstallment = $this->resolveRecord($functionEntry, $functionInstallment);

        DB::transaction(function () use ($request, $functionEntry, $functionInstallment) {
            $functionInstallment->update([
                'entry_date' => $request->validated('entry_date'),
                'name' => $request->validated('name'),
                'mode' => $request->validated('mode'),
                'amount_minor' => Money::toMinor($request->validated('amount')),
                'note' => $request->validated('note'),
            ]);

            $this->attachmentService->storeFor($functionInstallment, $request->file('attachments', []), $request->user());
            $this->totalsService->recalculate($functionEntry);
        });

        return $this->back($functionEntry, 'installments', 'Installment updated.');
    }

    public function destroy(Request $request, FunctionEntry $functionEntry, FunctionInstallment $functionInstallment): RedirectResponse
    {
        $this->authorizeEntry($request, $functionEntry);
        $functionInstallment = $this->resolveRecord($functionEntry, $functionInstallment);

        DB::transaction(function () use ($functionEntry, $functionInstallment) {
            $functionInstallment->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));
            $functionInstallment->delete();
            $this->totalsService->recalculate($functionEntry);
        });

        return $this->back($functionEntry, 'installments', 'Installment removed.');
    }

    private function authorizeEntry(Request $request, FunctionEntry $functionEntry): void
    {
        $this->authorize('update', $functionEntry);
        abort_unless((int) $functionEntry->venue_id === (int) $request->session()->get('selected_venue_id'), 404);
    }

    private function resolveRecord(FunctionEntry $functionEntry, FunctionInstallment $functionInstallment): FunctionInstallment
    {
        abort_unless((int) $functionInstallment->function_entry_id === (int) $functionEntry->id, 404);

        return $functionInstallment->loadMissing('attachments');
    }

    private function back(FunctionEntry $functionEntry, string $tab, string $status): RedirectResponse
    {
        return redirect()
            ->route('employee.functions.edit', ['functionEntry' => $functionEntry->id, 'tab' => $tab])
            ->with('status', $status);
    }
}
