<?php

namespace App\Http\Controllers\Employee\Functions;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\FunctionDiscount;
use App\Models\FunctionEntry;
use App\Models\FunctionExtraCharge;
use App\Models\FunctionInstallment;
use App\Services\Files\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FunctionAttachmentController extends Controller
{
    public function __construct(private AttachmentService $attachmentService)
    {
    }

    public function preview(Request $request, FunctionEntry $functionEntry, Attachment $attachment)
    {
        $this->authorizeEntry($request, $functionEntry, 'view');
        $attachment = $this->resolveAttachment($functionEntry, $attachment);

        abort_unless($attachment->canPreviewInline(), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->storage_path,
            $attachment->original_name,
            ['Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"']
        );
    }

    public function download(Request $request, FunctionEntry $functionEntry, Attachment $attachment)
    {
        $this->authorizeEntry($request, $functionEntry, 'view');
        $attachment = $this->resolveAttachment($functionEntry, $attachment);

        return Storage::disk($attachment->disk)->download($attachment->storage_path, $attachment->original_name);
    }

    public function destroy(Request $request, FunctionEntry $functionEntry, Attachment $attachment): RedirectResponse
    {
        $this->authorizeEntry($request, $functionEntry, 'update');
        $attachment = $this->resolveAttachment($functionEntry, $attachment);

        $this->attachmentService->delete($attachment);

        return back()->with('status', 'Attachment removed.');
    }

    private function authorizeEntry(Request $request, FunctionEntry $functionEntry, string $ability): void
    {
        $this->authorize($ability, $functionEntry);
        abort_unless((int) $functionEntry->venue_id === (int) $request->session()->get('selected_venue_id'), 404);
    }

    private function resolveAttachment(FunctionEntry $functionEntry, Attachment $attachment): Attachment
    {
        $entryId = $functionEntry->getKey();

        return Attachment::query()
            ->whereKey($attachment->getKey())
            ->where(function ($query) use ($entryId) {
                $query
                    ->where(function ($builder) use ($entryId) {
                        $builder
                            ->where('attachable_type', FunctionEntry::class)
                            ->where('attachable_id', $entryId);
                    })
                    ->orWhere(function ($builder) use ($entryId) {
                        $builder
                            ->where('attachable_type', FunctionExtraCharge::class)
                            ->whereIn('attachable_id', FunctionExtraCharge::query()->where('function_entry_id', $entryId)->select('id'));
                    })
                    ->orWhere(function ($builder) use ($entryId) {
                        $builder
                            ->where('attachable_type', FunctionInstallment::class)
                            ->whereIn('attachable_id', FunctionInstallment::query()->where('function_entry_id', $entryId)->select('id'));
                    })
                    ->orWhere(function ($builder) use ($entryId) {
                        $builder
                            ->where('attachable_type', FunctionDiscount::class)
                            ->whereIn('attachable_id', FunctionDiscount::query()->where('function_entry_id', $entryId)->select('id'));
                    });
            })
            ->firstOrFail();
    }
}
