<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="crm-section-title">Daily Billing</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Create Daily Billing Entry</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Add a billing row for the current venue and attach supporting files in the same screen.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="crm-chip bg-slate-950 text-white">{{ $currentVenue->name }}</span>
                <button type="button" onclick="window.print()" class="crm-button crm-button-secondary justify-center px-4 py-2 crm-print-hidden">
                    Print page
                </button>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <form method="POST" action="{{ route('employee.daily-billing.store') }}" enctype="multipart/form-data">
            @csrf
            @include('ledgers.partials.form-fields', [
                'attachmentRoutes' => [
                    'destroy' => 'employee.daily-billing.attachments.destroy',
                    'download' => 'employee.daily-billing.attachments.download',
                    'preview' => 'employee.daily-billing.attachments.preview',
                ],
                'currentVenue' => $currentVenue,
                'entry' => $entry,
                'entryRouteParameter' => 'dailyBilling',
                'isEditing' => false,
                'moduleDescription' => 'Daily billing follows the same venue-scoped file and totals rules as daily income.',
                'moduleLabel' => 'Daily Billing',
                'moduleSlug' => 'daily-billing',
                'vendorOptions' => collect(),
            ])
        </form>

        <aside class="xl:sticky xl:top-24 xl:self-start">
            @include('ledgers.partials.sidebar-summary', [
                'entry' => $entry,
                'workspaceTotals' => ['focus' => ['label' => 'Latest date total', 'entry_date' => null, 'entry_count' => 0, 'amount_minor' => 0], 'grand' => ['entry_count' => 0, 'amount_minor' => 0], 'date_totals' => []],
            ])
        </aside>
    </div>
</x-app-layout>
