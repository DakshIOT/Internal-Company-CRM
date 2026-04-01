<x-app-layout>
    <x-slot name="header">
        <div class="crm-page-header">
            <div class="crm-page-heading">
                <p class="crm-section-title">Vendor Entry</p>
                <h1 class="crm-page-title">Create Vendor Entry</h1>
                <p class="crm-page-description">
                    Choose one of the current venue vendor slots, set the amount, and attach the supporting files in one pass.
                </p>
            </div>
            <div class="crm-page-header-actions">
                <span class="crm-chip bg-slate-950 text-white">{{ $currentVenue->name }}</span>
                <button type="button" onclick="window.print()" class="crm-button crm-button-secondary justify-center px-4 py-2 crm-print-hidden">
                    Print page
                </button>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <form method="POST" action="{{ route('employee.vendor-entries.store') }}" enctype="multipart/form-data">
            @csrf
            @include('ledgers.partials.form-fields', [
                'attachmentRoutes' => [
                    'destroy' => 'employee.vendor-entries.attachments.destroy',
                    'download' => 'employee.vendor-entries.attachments.download',
                    'preview' => 'employee.vendor-entries.attachments.preview',
                ],
                'currentVenue' => $currentVenue,
                'entry' => $entry,
                'entryRouteParameter' => 'vendorEntry',
                'isEditing' => false,
                'moduleDescription' => 'Vendor Entry is available only to Employee Type B and stays locked to the selected venue vendor slots.',
                'moduleLabel' => 'Vendor Entry',
                'moduleSlug' => 'vendor-entries',
                'vendorOptions' => $vendorOptions,
            ])
        </form>

        <aside class="xl:sticky xl:top-24 xl:self-start">
            @include('ledgers.partials.sidebar-summary', [
                'entry' => $entry,
                'workspaceTotals' => ['focus' => ['label' => 'Latest date total', 'entry_date' => null, 'entry_count' => 0, 'amount_minor' => 0], 'grand' => ['entry_count' => 0, 'amount_minor' => 0], 'date_totals' => [], 'vendor_totals' => []],
            ])
        </aside>
    </div>
</x-app-layout>
