<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="crm-section-title">Admin Income</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Create Admin Income Entry</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Add a global admin income row with secure attachments and server-owned totals.
            </p>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <form method="POST" action="{{ route('admin.admin-income.store') }}" enctype="multipart/form-data">
            @csrf
            @include('ledgers.partials.form-fields', [
                'attachmentRoutes' => [
                    'destroy' => 'admin.admin-income.attachments.destroy',
                    'download' => 'admin.admin-income.attachments.download',
                    'preview' => 'admin.admin-income.attachments.preview',
                ],
                'currentVenue' => null,
                'entry' => $entry,
                'entryRouteParameter' => 'adminIncome',
                'indexRoute' => 'admin.admin-income.index',
                'isEditing' => false,
                'moduleDescription' => 'Admin Income stays global and does not inherit employee venue-session rules.',
                'moduleLabel' => 'Admin Income',
                'moduleSlug' => 'admin-income',
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
