<?php

namespace App\Http\Controllers\Employee\Functions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Functions\FunctionExtraChargeRequest;
use App\Models\FunctionEntry;
use App\Models\FunctionExtraCharge;
use App\Services\Files\AttachmentService;
use App\Services\Functions\FunctionEntryTotalsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FunctionExtraChargeController extends Controller
{
    public function __construct(
        private FunctionEntryTotalsService $totalsService,
        private AttachmentService $attachmentService,
    ) {
    }

    public function store(FunctionExtraChargeRequest $request, FunctionEntry $functionEntry): RedirectResponse
    {
        $this->authorizeEntry($request, $functionEntry);

        DB::transaction(function () use ($request, $functionEntry) {
            $record = $functionEntry->extraCharges()->create([
                'entry_date' => $request->validated('entry_date'),
                'name' => $request->validated('name'),
                'mode' => $request->validated('mode'),
                'amount_minor' => Money::toMinor($request->validated('amount')),
                'note' => $request->validated('note'),
            ]);

            $this->attachmentService->storeFor($record, $request->file('attachments', []), $request->user());
            $this->totalsService->recalculate($functionEntry);
        });

        return $this->back($functionEntry, 'extra-charges', 'Extra charge added.');
    }

    public function update(
        FunctionExtraChargeRequest $request,
        FunctionEntry $functionEntry,
        FunctionExtraCharge $functionExtraCharge
    ): RedirectResponse {
        $this->authorizeEntry($request, $functionEntry);
        $functionExtraCharge = $this->resolveRecord($functionEntry, $functionExtraCharge);

        DB::transaction(function () use ($request, $functionEntry, $functionExtraCharge) {
            $functionExtraCharge->update([
                'entry_date' => $request->validated('entry_date'),
                'name' => $request->validated('name'),
                'mode' => $request->validated('mode'),
                'amount_minor' => Money::toMinor($request->validated('amount')),
                'note' => $request->validated('note'),
            ]);

            $this->attachmentService->storeFor($functionExtraCharge, $request->file('attachments', []), $request->user());
            $this->totalsService->recalculate($functionEntry);
        });

        return $this->back($functionEntry, 'extra-charges', 'Extra charge updated.');
    }

    public function destroy(Request $request, FunctionEntry $functionEntry, FunctionExtraCharge $functionExtraCharge): RedirectResponse
    {
        $this->authorizeEntry($request, $functionEntry);
        $functionExtraCharge = $this->resolveRecord($functionEntry, $functionExtraCharge);

        DB::transaction(function () use ($functionEntry, $functionExtraCharge) {
            $functionExtraCharge->attachments->each(fn ($attachment) => $this->attachmentService->delete($attachment));
            $functionExtraCharge->delete();
            $this->totalsService->recalculate($functionEntry);
        });

        return $this->back($functionEntry, 'extra-charges', 'Extra charge removed.');
    }

    private function authorizeEntry(Request $request, FunctionEntry $functionEntry): void
    {
        $this->authorize('update', $functionEntry);
        abort_unless((int) $functionEntry->venue_id === (int) $request->session()->get('selected_venue_id'), 404);
    }

    private function resolveRecord(FunctionEntry $functionEntry, FunctionExtraCharge $functionExtraCharge): FunctionExtraCharge
    {
        abort_unless((int) $functionExtraCharge->function_entry_id === (int) $functionEntry->id, 404);

        return $functionExtraCharge->loadMissing('attachments');
    }

    private function back(FunctionEntry $functionEntry, string $tab, string $status): RedirectResponse
    {
        return redirect()
            ->route('employee.functions.edit', ['functionEntry' => $functionEntry->id, 'tab' => $tab])
            ->with('status', $status);
    }
}
