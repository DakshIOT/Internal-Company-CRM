<x-app-layout>
    <x-slot name="header">
        <div class="crm-page-header">
            <div class="crm-page-heading">
                <p class="crm-section-title">Daily Income</p>
                <h1 class="crm-page-title">{{ $entry->name }}</h1>
                <p class="crm-page-description">
                    Update the row amount, notes, and file set without leaving the venue-scoped income workspace.
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
            <form method="POST" action="{{ route('employee.daily-income.update', $entry) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('ledgers.partials.form-fields', [
                    'attachmentRoutes' => [
                        'destroy' => 'employee.daily-income.attachments.destroy',
                        'download' => 'employee.daily-income.attachments.download',
                        'preview' => 'employee.daily-income.attachments.preview',
                    ],
                    'currentVenue' => $currentVenue,
                    'entry' => $entry,
                    'entryRouteParameter' => 'dailyIncome',
                    'isEditing' => true,
                    'moduleDescription' => 'Files remain secured to this single row and cannot escape the selected venue.',
                    'moduleLabel' => 'Daily Income',
                    'moduleSlug' => 'daily-income',
                    'vendorOptions' => collect(),
                ])
            </form>

            <form method="POST" action="{{ route('employee.daily-income.destroy', $entry) }}" onsubmit="return confirm('Delete this daily income entry?');" class="crm-print-hidden">
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
        <form id="attachment-delete-{{ $attachment->id }}" method="POST" action="{{ route('employee.daily-income.attachments.destroy', ['dailyIncome' => $entry, 'attachment' => $attachment]) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</x-app-layout>
