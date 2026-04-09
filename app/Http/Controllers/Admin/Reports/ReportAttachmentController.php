<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\AdminIncomeEntry;
use App\Models\Attachment;
use App\Models\DailyBillingEntry;
use App\Models\DailyIncomeEntry;
use App\Models\FunctionEntry;
use App\Models\VendorEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportAttachmentController extends Controller
{
    public function preview(Request $request, Attachment $attachment)
    {
        $this->authorizeAttachment($request, $attachment);
        abort_unless($attachment->canPreviewInline(), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->storage_path,
            $attachment->original_name,
            ['Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"']
        );
    }

    public function download(Request $request, Attachment $attachment)
    {
        $this->authorizeAttachment($request, $attachment);

        return Storage::disk($attachment->disk)->download($attachment->storage_path, $attachment->original_name);
    }

    private function authorizeAttachment(Request $request, Attachment $attachment): void
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $allowedTypes = [
            FunctionEntry::class,
            DailyIncomeEntry::class,
            DailyBillingEntry::class,
            VendorEntry::class,
            AdminIncomeEntry::class,
        ];

        abort_unless(in_array($attachment->attachable_type, $allowedTypes, true), 404);
    }
}

