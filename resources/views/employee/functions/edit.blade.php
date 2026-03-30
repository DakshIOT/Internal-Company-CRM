@php use App\Support\Money; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="crm-section-title">Function Entry</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">{{ $functionEntry->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Base details, packages, extra charges, installments, discounts, and attachments all stay attached to this one venue-scoped entry.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="crm-chip bg-cyan-50 text-cyan-700">{{ $currentVenue->name }}</span>
                <span class="crm-chip bg-white text-slate-500">{{ optional($functionEntry->entry_date)->format('d M Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
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

            <section class="crm-panel p-6" x-data="{ activeTab: @js($selectedTab) }">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="crm-section-title">Action center</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">Packages, charges, installments, and discounts</h2>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="crm-tab" :class="{ 'crm-tab-active': activeTab === 'packages' }" @click="activeTab = 'packages'">Packages</button>
                        <button type="button" class="crm-tab" :class="{ 'crm-tab-active': activeTab === 'extra-charges' }" @click="activeTab = 'extra-charges'">Extra Charges</button>
                        <button type="button" class="crm-tab" :class="{ 'crm-tab-active': activeTab === 'installments' }" @click="activeTab = 'installments'">Installments</button>
                        <button type="button" class="crm-tab" :class="{ 'crm-tab-active': activeTab === 'discounts' }" @click="activeTab = 'discounts'">Discounts</button>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-[1.5rem] border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="font-semibold">Review the validation issues in the active section and submit again.</p>
                    </div>
                @endif

                <div class="mt-6 space-y-6" x-show="activeTab === 'packages'" x-cloak>
                    <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <p class="crm-section-title">Add package</p>
                        <form method="POST" action="{{ route('employee.functions.packages.store', $functionEntry) }}" class="mt-4 flex flex-col gap-3 lg:flex-row">
                            @csrf
                            <select name="package_id" class="crm-input w-full">
                                <option value="">Select an assigned package</option>
                                @foreach ($availablePackages as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }}{{ $package->code ? ' | '.$package->code : '' }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="crm-button crm-button-primary justify-center">Add package</button>
                        </form>
                        <x-input-error :messages="$errors->get('package_id')" class="mt-2" />
                        @if ($availablePackages->isEmpty())
                            <p class="mt-3 text-sm text-slate-500">All assigned packages are already linked to this function entry.</p>
                        @endif
                    </article>

                    <div class="space-y-5">
                        @forelse ($functionEntry->packages as $functionPackage)
                            <article class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="crm-section-title">Package</p>
                                        <h3 class="mt-2 text-xl font-semibold text-slate-950">{{ $functionPackage->name_snapshot }}</h3>
                                        <p class="mt-2 text-sm text-slate-500">{{ $functionPackage->code_snapshot ?: 'No code' }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="crm-chip bg-cyan-50 text-cyan-700">Total {{ Money::formatMinor($functionPackage->total_minor) }}</span>
                                        <form method="POST" action="{{ route('employee.functions.packages.destroy', [$functionEntry, $functionPackage]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="crm-button border border-rose-200 bg-rose-50 px-4 py-2 text-rose-600 hover:border-rose-300">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('employee.functions.packages.update', [$functionEntry, $functionPackage]) }}" class="mt-5 space-y-4">
                                    @csrf
                                    @method('PUT')

                                    <div class="crm-table-wrap hidden lg:block">
                                        <table class="crm-table">
                                            <thead>
                                                <tr>
                                                    <th>Select</th>
                                                    <th>Service</th>
                                                    <th>Persons</th>
                                                    <th>Rate</th>
                                                    <th>Extra Charge</th>
                                                    <th>Notes</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($functionPackage->serviceLines as $line)
                                                    <tr x-data="{ persons: '{{ $line->persons }}', rate: '{{ number_format($line->rate_minor / 100, 2, '.', '') }}', extra: '{{ number_format($line->extra_charge_minor / 100, 2, '.', '') }}' }">
                                                        <td>
                                                            <input type="checkbox" name="service_lines[{{ $line->id }}][is_selected]" value="1" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked($line->is_selected) />
                                                        </td>
                                                        <td>
                                                            <p class="font-semibold text-slate-900">{{ $line->item_name_snapshot }}</p>
                                                        </td>
                                                        <td>
                                                            <x-text-input name="service_lines[{{ $line->id }}][persons]" x-model="persons" :value="old('service_lines.'.$line->id.'.persons', $line->persons)" class="crm-input w-full" />
                                                        </td>
                                                        <td>
                                                            <x-text-input name="service_lines[{{ $line->id }}][rate]" x-model="rate" :value="old('service_lines.'.$line->id.'.rate', Money::formatMinor($line->rate_minor))" class="crm-input w-full bg-slate-100" readonly />
                                                        </td>
                                                        <td>
                                                            <x-text-input name="service_lines[{{ $line->id }}][extra_charge]" x-model="extra" :value="old('service_lines.'.$line->id.'.extra_charge', Money::formatMinor($line->extra_charge_minor))" class="crm-input w-full" />
                                                        </td>
                                                        <td>
                                                            <textarea name="service_lines[{{ $line->id }}][notes]" rows="2" class="crm-input w-full">{{ old('service_lines.'.$line->id.'.notes', $line->notes) }}</textarea>
                                                        </td>
                                                        <td class="font-semibold text-slate-900" x-text="(((parseFloat(persons || 0) * parseFloat(rate || 0)) + parseFloat(extra || 0))).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="space-y-3 lg:hidden">
                                        @foreach ($functionPackage->serviceLines as $line)
                                            <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4" x-data="{ persons: '{{ $line->persons }}', rate: '{{ number_format($line->rate_minor / 100, 2, '.', '') }}', extra: '{{ number_format($line->extra_charge_minor / 100, 2, '.', '') }}' }">
                                                <label class="flex items-start gap-3">
                                                    <input type="checkbox" name="service_lines[{{ $line->id }}][is_selected]" value="1" class="mt-1 rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked($line->is_selected) />
                                                    <span>
                                                        <span class="block font-semibold text-slate-900">{{ $line->item_name_snapshot }}</span>
                                                        <span class="mt-1 block text-xs text-slate-500">Live row total <span x-text="(((parseFloat(persons || 0) * parseFloat(rate || 0)) + parseFloat(extra || 0))).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span></span>
                                                    </span>
                                                </label>
                                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                                    <x-text-input name="service_lines[{{ $line->id }}][persons]" x-model="persons" :value="old('service_lines.'.$line->id.'.persons', $line->persons)" class="crm-input w-full" placeholder="Persons" />
                                                    <x-text-input name="service_lines[{{ $line->id }}][rate]" x-model="rate" :value="old('service_lines.'.$line->id.'.rate', Money::formatMinor($line->rate_minor))" class="crm-input w-full bg-slate-100" placeholder="Rate" readonly />
                                                    <x-text-input name="service_lines[{{ $line->id }}][extra_charge]" x-model="extra" :value="old('service_lines.'.$line->id.'.extra_charge', Money::formatMinor($line->extra_charge_minor))" class="crm-input w-full" placeholder="Extra charge" />
                                                    <textarea name="service_lines[{{ $line->id }}][notes]" rows="2" class="crm-input w-full sm:col-span-2" placeholder="Notes">{{ old('service_lines.'.$line->id.'.notes', $line->notes) }}</textarea>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                        <button type="submit" class="crm-button crm-button-primary justify-center">Save package lines</button>
                                    </div>
                                </form>
                            </article>
                        @empty
                            <article class="rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                                No packages linked yet. Add one from the assigned package list above.
                            </article>
                        @endforelse
                    </div>
                </div>

                <div class="mt-6 space-y-6" x-show="activeTab === 'extra-charges'" x-cloak>
                    <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <p class="crm-section-title">Add extra charge</p>
                        <form method="POST" action="{{ route('employee.functions.extra-charges.store', $functionEntry) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf
                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <x-text-input name="entry_date" type="date" :value="old('entry_date', optional($functionEntry->entry_date)->format('Y-m-d'))" class="crm-input w-full" />
                                <x-text-input name="name" :value="old('name')" class="crm-input w-full" placeholder="Charge name" />
                                <select name="mode" class="crm-input w-full">
                                    @foreach ($modeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-text-input name="amount" :value="old('amount')" class="crm-input w-full" placeholder="Amount" />
                            </div>
                            <textarea name="note" rows="3" class="crm-input w-full" placeholder="Note">{{ old('note') }}</textarea>
                            @include('employee.functions.partials.attachments', [
                                'attachable' => new \App\Models\FunctionExtraCharge(),
                                'functionEntry' => $functionEntry,
                                'inputId' => 'new_extra_charge_attachments',
                                'emptyMessage' => null,
                            ])
                            <button type="submit" class="crm-button crm-button-primary justify-center">Add extra charge</button>
                        </form>
                    </article>

                    <div class="space-y-5">
                        @foreach ($functionEntry->extraCharges as $record)
                            <article class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm">
                                <form method="POST" action="{{ route('employee.functions.extra-charges.update', [$functionEntry, $record]) }}" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <x-text-input name="entry_date" type="date" :value="old('entry_date', optional($record->entry_date)->format('Y-m-d'))" class="crm-input w-full" />
                                        <x-text-input name="name" :value="old('name', $record->name)" class="crm-input w-full" />
                                        <select name="mode" class="crm-input w-full">
                                            @foreach ($modeOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($record->mode === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <x-text-input name="amount" :value="old('amount', Money::formatMinor($record->amount_minor))" class="crm-input w-full" />
                                    </div>
                                    <textarea name="note" rows="3" class="crm-input w-full">{{ old('note', $record->note) }}</textarea>
                                    @include('employee.functions.partials.attachments', [
                                        'attachable' => $record,
                                        'functionEntry' => $functionEntry,
                                        'inputId' => 'extra_charge_attachments_'.$record->id,
                                        'emptyMessage' => 'No files linked to this extra charge.',
                                    ])
                                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                        <button type="submit" class="crm-button crm-button-primary justify-center">Save extra charge</button>
                                        <button type="submit" form="delete-extra-charge-{{ $record->id }}" class="crm-button border border-rose-200 bg-rose-50 justify-center text-rose-600 hover:border-rose-300">Remove</button>
                                    </div>
                                </form>
                                <form id="delete-extra-charge-{{ $record->id }}" method="POST" action="{{ route('employee.functions.extra-charges.destroy', [$functionEntry, $record]) }}">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 space-y-6" x-show="activeTab === 'installments'" x-cloak>
                    <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <p class="crm-section-title">Add installment</p>
                        <form method="POST" action="{{ route('employee.functions.installments.store', $functionEntry) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf
                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <x-text-input name="entry_date" type="date" :value="optional($functionEntry->entry_date)->format('Y-m-d')" class="crm-input w-full" />
                                <x-text-input name="name" class="crm-input w-full" placeholder="Installment name" />
                                <select name="mode" class="crm-input w-full">
                                    @foreach ($modeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-text-input name="amount" class="crm-input w-full" placeholder="Amount" />
                            </div>
                            <textarea name="note" rows="3" class="crm-input w-full" placeholder="Note"></textarea>
                            @include('employee.functions.partials.attachments', [
                                'attachable' => new \App\Models\FunctionInstallment(),
                                'functionEntry' => $functionEntry,
                                'inputId' => 'new_installment_attachments',
                                'emptyMessage' => null,
                            ])
                            <button type="submit" class="crm-button crm-button-primary justify-center">Add installment</button>
                        </form>
                    </article>

                    <div class="space-y-5">
                        @foreach ($functionEntry->installments as $record)
                            <article class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm">
                                <form method="POST" action="{{ route('employee.functions.installments.update', [$functionEntry, $record]) }}" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <x-text-input name="entry_date" type="date" :value="optional($record->entry_date)->format('Y-m-d')" class="crm-input w-full" />
                                        <x-text-input name="name" :value="$record->name" class="crm-input w-full" />
                                        <select name="mode" class="crm-input w-full">
                                            @foreach ($modeOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($record->mode === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <x-text-input name="amount" :value="Money::formatMinor($record->amount_minor)" class="crm-input w-full" />
                                    </div>
                                    <textarea name="note" rows="3" class="crm-input w-full">{{ $record->note }}</textarea>
                                    @include('employee.functions.partials.attachments', [
                                        'attachable' => $record,
                                        'functionEntry' => $functionEntry,
                                        'inputId' => 'installment_attachments_'.$record->id,
                                        'emptyMessage' => 'No files linked to this installment.',
                                    ])
                                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                        <button type="submit" class="crm-button crm-button-primary justify-center">Save installment</button>
                                        <button type="submit" form="delete-installment-{{ $record->id }}" class="crm-button border border-rose-200 bg-rose-50 justify-center text-rose-600 hover:border-rose-300">Remove</button>
                                    </div>
                                </form>
                                <form id="delete-installment-{{ $record->id }}" method="POST" action="{{ route('employee.functions.installments.destroy', [$functionEntry, $record]) }}">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 space-y-6" x-show="activeTab === 'discounts'" x-cloak>
                    <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <p class="crm-section-title">Add discount</p>
                        <form method="POST" action="{{ route('employee.functions.discounts.store', $functionEntry) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                            @csrf
                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <x-text-input name="entry_date" type="date" :value="optional($functionEntry->entry_date)->format('Y-m-d')" class="crm-input w-full" />
                                <x-text-input name="name" class="crm-input w-full" placeholder="Discount name" />
                                <select name="mode" class="crm-input w-full">
                                    @foreach ($modeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-text-input name="amount" class="crm-input w-full" placeholder="Amount" />
                            </div>
                            <textarea name="note" rows="3" class="crm-input w-full" placeholder="Note"></textarea>
                            @include('employee.functions.partials.attachments', [
                                'attachable' => new \App\Models\FunctionDiscount(),
                                'functionEntry' => $functionEntry,
                                'inputId' => 'new_discount_attachments',
                                'emptyMessage' => null,
                            ])
                            <button type="submit" class="crm-button crm-button-primary justify-center">Add discount</button>
                        </form>
                    </article>

                    <div class="space-y-5">
                        @foreach ($functionEntry->discounts as $record)
                            <article class="rounded-[1.75rem] border border-slate-100 bg-white p-5 shadow-sm">
                                <form method="POST" action="{{ route('employee.functions.discounts.update', [$functionEntry, $record]) }}" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <x-text-input name="entry_date" type="date" :value="optional($record->entry_date)->format('Y-m-d')" class="crm-input w-full" />
                                        <x-text-input name="name" :value="$record->name" class="crm-input w-full" />
                                        <select name="mode" class="crm-input w-full">
                                            @foreach ($modeOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($record->mode === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <x-text-input name="amount" :value="Money::formatMinor($record->amount_minor)" class="crm-input w-full" />
                                    </div>
                                    <textarea name="note" rows="3" class="crm-input w-full">{{ $record->note }}</textarea>
                                    @include('employee.functions.partials.attachments', [
                                        'attachable' => $record,
                                        'functionEntry' => $functionEntry,
                                        'inputId' => 'discount_attachments_'.$record->id,
                                        'emptyMessage' => 'No files linked to this discount.',
                                    ])
                                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                        <button type="submit" class="crm-button crm-button-primary justify-center">Save discount</button>
                                        <button type="submit" form="delete-discount-{{ $record->id }}" class="crm-button border border-rose-200 bg-rose-50 justify-center text-rose-600 hover:border-rose-300">Remove</button>
                                    </div>
                                </form>
                                <form id="delete-discount-{{ $record->id }}" method="POST" action="{{ route('employee.functions.discounts.destroy', [$functionEntry, $record]) }}">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <form method="POST" action="{{ route('employee.functions.destroy', $functionEntry) }}" onsubmit="return confirm('Delete this function entry and its related records?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="crm-button border border-rose-200 bg-rose-50 justify-center text-rose-600 hover:border-rose-300">
                    Delete function entry
                </button>
            </form>
        </div>

        <aside class="xl:sticky xl:top-24 xl:self-start">
            @include('employee.functions.partials.summary', [
                'functionEntry' => $functionEntry,
                'workspaceTotals' => $workspaceTotals,
            ])
        </aside>
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
