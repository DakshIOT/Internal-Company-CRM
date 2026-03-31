<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="crm-section-title">Admin Income</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">{{ $entry->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Update the global admin income row, notes, and file set without any employee venue dependency.
                </p>
            </div>
            <span class="crm-chip bg-slate-950 text-white">Global Admin Context</span>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.admin-income.update', $entry) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('ledgers.partials.form-fields', [
                    'attachmentRoutes' => [
                        'destroy' => 'admin.admin-income.attachments.destroy',
                        'download' => 'admin.admin-income.attachments.download',
                        'preview' => 'admin.admin-income.attachments.preview',
                    ],
                    'currentVenue' => null,
                    'entry' => $entry,
                    'entryRouteParameter' => 'adminIncome',
                    'isEditing' => true,
                    'moduleDescription' => 'Admin Income is admin-only and secured independently from employee venue context.',
                    'moduleLabel' => 'Admin Income',
                    'moduleSlug' => 'admin-income',
                    'vendorOptions' => collect(),
                ])
            </form>

            <form method="POST" action="{{ route('admin.admin-income.destroy', $entry) }}" onsubmit="return confirm('Delete this admin income entry?');">
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
        <form id="attachment-delete-{{ $attachment->id }}" method="POST" action="{{ route('admin.admin-income.attachments.destroy', ['adminIncome' => $entry, 'attachment' => $attachment]) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</x-app-layout>
