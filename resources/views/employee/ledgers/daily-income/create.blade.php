<x-app-layout>
    <x-slot name="header">
        <div class="crm-page-header">
            <div class="crm-page-heading">
                <p class="crm-section-title">Daily Income</p>
                <h1 class="crm-page-title">Create Daily Income Entry</h1>
                <p class="crm-page-description">
                    Add the base income row, attach supporting files, and keep the amount tied to the current venue only.
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
        <form method="POST" action="{{ route('employee.daily-income.store') }}" enctype="multipart/form-data">
            @csrf
            @include('ledgers.partials.form-fields', [
                'attachmentRoutes' => [
                    'destroy' => 'employee.daily-income.attachments.destroy',
                    'download' => 'employee.daily-income.attachments.download',
                    'preview' => 'employee.daily-income.attachments.preview',
                ],
                'currentVenue' => $currentVenue,
                'entry' => $entry,
                'entryRouteParameter' => 'dailyIncome',
                'isEditing' => false,
                'moduleDescription' => 'Daily income is venue-scoped and totals update from stored amounts only.',
                'moduleLabel' => 'Daily Income',
                'moduleSlug' => 'daily-income',
                'vendorOptions' => collect(),
            ])
        </form>

        <aside>
            @include('ledgers.partials.sidebar-summary', [
                'entry' => $entry,
                'workspaceTotals' => ['focus' => ['label' => 'Latest date total', 'entry_date' => null, 'entry_count' => 0, 'amount_minor' => 0], 'grand' => ['entry_count' => 0, 'amount_minor' => 0], 'date_totals' => []],
            ])
        </aside>
    </div>
</x-app-layout>
