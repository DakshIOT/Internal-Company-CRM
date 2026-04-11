@php use App\Support\Money; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="crm-page-header">
            <div class="crm-page-heading">
                <p class="crm-section-title">Function Entry</p>
                <h1 class="crm-page-title">{{ $functionEntry->name }}</h1>
                <p class="crm-page-description">
                    Base details stay visible here. Packages, charges, installments, and discounts open in one focused modal.
                </p>
            </div>
            <div class="crm-page-header-actions">
                <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $currentVenue->name }}</span>
                <span class="crm-chip bg-white text-slate-500">{{ optional($functionEntry->entry_date)->format('d M Y') }}</span>
                <button type="button" onclick="window.print()" class="crm-button crm-button-secondary justify-center px-4 py-2 crm-print-hidden">
                    Print entry
                </button>
            </div>
        </div>
    </x-slot>

    <div
        class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]"
        x-data="{ actionModal: @js((bool) $selectedTab), activeTab: @js($selectedTab ?: 'packages') }"
    >
        <div class="space-y-6">
            <form method="POST" action="{{ route('employee.functions.update', $functionEntry) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @include('employee.functions.partials.form', [
                    'currentVenue' => $currentVenue,
                    'functionEntry' => $functionEntry,
                    'isEditing' => true,
                ])
            </form>

            <section class="crm-panel p-6 crm-print-hidden">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="crm-section-title">Quick actions</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Open one section at a time</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Pick the part you want to update. This keeps the page simple while still giving you full control over the entry.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="crm-button crm-button-primary px-4 py-2" @click="activeTab = 'packages'; actionModal = true">Packages</button>
                        <button type="button" class="crm-button crm-button-secondary px-4 py-2" @click="activeTab = 'extra-charges'; actionModal = true">Extra Charges</button>
                        <button type="button" class="crm-button crm-button-secondary px-4 py-2" @click="activeTab = 'installments'; actionModal = true">Installments</button>
                        <button type="button" class="crm-button crm-button-secondary px-4 py-2" @click="activeTab = 'discounts'; actionModal = true">Discounts</button>
                    </div>
                </div>
            </section>

            <form method="POST" action="{{ route('employee.functions.destroy', $functionEntry) }}" onsubmit="return confirm('Delete this function entry and its related records?');" class="crm-print-hidden">
                @csrf
                @method('DELETE')
                <button type="submit" class="crm-button border border-rose-200 bg-rose-50 justify-center text-rose-600 hover:border-rose-300">
                    Delete function entry
                </button>
            </form>
        </div>

        <aside class="space-y-6">
            @include('employee.functions.partials.summary', [
                'functionEntry' => $functionEntry,
                'workspaceTotals' => $workspaceTotals,
            ])
        </aside>

        <div
            x-cloak
            x-show="actionModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm"
            @click.self="actionModal = false"
            @keydown.escape.window="actionModal = false"
        >
            <div class="max-h-[88vh] w-full max-w-6xl overflow-y-auto rounded-[1.75rem] border border-white/20 bg-white p-6 shadow-2xl sm:p-7">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <p class="crm-section-title">Function action center</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ $functionEntry->name }}</h2>
                    </div>
                    <button type="button" class="rounded-full border border-slate-200 px-3 py-1 text-sm text-slate-500" @click="actionModal = false">
                        Close
                    </button>
                </div>

                <div class="mt-6">
                    @include('employee.functions.partials.action-center-modal', [
                        'availablePackages' => $availablePackages,
                        'functionEntry' => $functionEntry,
                        'modeOptions' => $modeOptions,
                    ])
                </div>
            </div>
        </div>
    </div>

    @php
        $allAttachments = $functionEntry->attachments
            ->concat($functionEntry->extraCharges->flatMap->attachments)
            ->concat($functionEntry->installments->flatMap->attachments)
            ->concat($functionEntry->discounts->flatMap->attachments);
    @endphp

    @foreach ($allAttachments as $attachment)
        <form id="attachment-delete-{{ $attachment->id }}" method="POST" action="{{ route('employee.functions.attachments.destroy', ['functionEntry' => $functionEntry, 'attachment' => $attachment]) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
</x-app-layout>
