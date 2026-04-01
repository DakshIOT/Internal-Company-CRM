<x-app-layout>
    <x-slot name="header">
        <div class="crm-page-header">
            <div class="crm-page-heading">
                <p class="crm-section-title">Daily Billing</p>
                <h1 class="crm-page-title">{{ $entry->name }}</h1>
                <p class="crm-page-description">
                    Update the billing row, notes, and file set while keeping every action locked to the active venue.
                </p>
            </div>
            <div class="crm-page-header-actions">
                <span class="crm-chip bg-slate-950 text-white">{{ $currentVenue->name }}</span>
                <button type="button" onclick="window.print()" class="crm-button crm-button-secondary justify-center px-4 py-2 crm-print-hidden">
                    Print entry
                </button>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-6">
            <form method="POST" action="{{ route('employee.daily-billing.update', $entry) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('ledgers.partials.form-fields', [
                    'attachmentRoutes' => [
                        'destroy' => 'employee.daily-billing.attachments.destroy',
                        'download' => 'employee.daily-billing.attachments.download',
                        'preview' => 'employee.daily-billing.attachments.preview',
                    ],
                    'currentVenue' => $currentVenue,
                    'entry' => $entry,
                    'entryRouteParameter' => 'dailyBilling',
                    'isEditing' => true,
                    'moduleDescription' => 'Stored totals come from server-side amounts only, never from client calculations.',
                    'moduleLabel' => 'Daily Billing',
                    'moduleSlug' => 'daily-billing',
                    'vendorOptions' => collect(),
                ])
            </form>

            <form method="POST" action="{{ route('employee.daily-billing.destroy', $entry) }}" onsubmit="return confirm('Delete this daily billing entry?');" class="crm-print-hidden">
                @csrf
                @method('DELETE')
                <button type="submit" class="crm-button border border-rose-200 bg-rose-50 justify-center text-rose-600 hover:border-rose-300">
                    Delete entry
                </button>
            </form>
        </div>

        <aside class="xl:sticky xl:top-24 xl:self-start">
            @include('ledgers.partials.sidebar-summary', ['entry' => $entry, 'workspaceTotals' => $workspaceTotals])
        </aside>
    </div>

    @foreach ($entry->attachments as $attachment)
        <form id="attachment-delete-{{ $attachment->id }}" method="POST" action="{{ route('employee.daily-billing.attachments.destroy', ['dailyBilling' => $entry, 'attachment' => $attachment]) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</x-app-layout>
