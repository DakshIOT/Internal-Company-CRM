<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="crm-section-title">Vendor Entry</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">{{ $entry->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Adjust the vendor slot, amount, notes, and file set while keeping totals visible for the current venue.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="crm-chip bg-slate-950 text-white">{{ $currentVenue->name }}</span>
                @if ($entry->venueVendor)
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $entry->venueVendor->name }}</span>
                @endif
                <button type="button" onclick="window.print()" class="crm-button crm-button-secondary justify-center px-4 py-2 crm-print-hidden">
                    Print entry
                </button>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-6">
            <form method="POST" action="{{ route('employee.vendor-entries.update', $entry) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('ledgers.partials.form-fields', [
                    'attachmentRoutes' => [
                        'destroy' => 'employee.vendor-entries.attachments.destroy',
                        'download' => 'employee.vendor-entries.attachments.download',
                        'preview' => 'employee.vendor-entries.attachments.preview',
                    ],
                    'currentVenue' => $currentVenue,
                    'entry' => $entry,
                    'entryRouteParameter' => 'vendorEntry',
                    'isEditing' => true,
                    'moduleDescription' => 'Vendor totals and date totals stay server-calculated while files remain attached to this one row.',
                    'moduleLabel' => 'Vendor Entry',
                    'moduleSlug' => 'vendor-entries',
                    'vendorOptions' => $vendorOptions,
                ])
            </form>

            <form method="POST" action="{{ route('employee.vendor-entries.destroy', $entry) }}" onsubmit="return confirm('Delete this vendor entry?');" class="crm-print-hidden">
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
        <form id="attachment-delete-{{ $attachment->id }}" method="POST" action="{{ route('employee.vendor-entries.attachments.destroy', ['vendorEntry' => $entry, 'attachment' => $attachment]) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</x-app-layout>
