@php use App\Support\Money; @endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="crm-section-title">Action center</p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-950">Manage one section at a time</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Add rows in table form, then open detailed edits only when required.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="crm-tab" :class="{ 'crm-tab-active': activeTab === 'packages' }" @click="activeTab = 'packages'">Packages</button>
            <button type="button" class="crm-tab" :class="{ 'crm-tab-active': activeTab === 'extra-charges' }" @click="activeTab = 'extra-charges'">Extra Charges</button>
            <button type="button" class="crm-tab" :class="{ 'crm-tab-active': activeTab === 'installments' }" @click="activeTab = 'installments'">Installments</button>
            <button type="button" class="crm-tab" :class="{ 'crm-tab-active': activeTab === 'discounts' }" @click="activeTab = 'discounts'">Discounts</button>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Review the validation issues in the active section and submit again.</p>
        </div>
    @endif

    <div class="space-y-6" x-show="activeTab === 'packages'" x-cloak>
        <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="crm-section-title">Add package</p>
                    <p class="mt-2 text-sm text-slate-600">Only packages assigned to this employee in the selected venue appear here.</p>
                </div>
                <form method="POST" action="{{ route('employee.functions.packages.store', $functionEntry) }}" class="flex w-full max-w-xl flex-col gap-3 sm:flex-row">
                    @csrf
                    <select name="package_id" class="crm-input w-full">
                        <option value="">Select an assigned package</option>
                        @foreach ($availablePackages as $package)
                            <option value="{{ $package->id }}">{{ $package->name }}{{ $package->code ? ' | '.$package->code : '' }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="crm-button crm-button-primary justify-center sm:min-w-[10rem]">Add package</button>
                </form>
            </div>
            <x-input-error :messages="$errors->get('package_id')" class="mt-2" />
        </article>

        @forelse ($functionEntry->packages as $functionPackage)
            <article class="crm-panel p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="crm-section-title">Package</p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-950">{{ $functionPackage->name_snapshot }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $functionPackage->code_snapshot ?: 'No code' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 crm-print-hidden">
                        <span class="crm-chip bg-cyan-50 text-cyan-700">Package total {{ Money::formatMinor($functionPackage->total_minor) }}</span>
                        <form method="POST" action="{{ route('employee.functions.packages.destroy', [$functionEntry, $functionPackage]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="crm-button border border-rose-200 bg-rose-50 px-4 py-2 text-rose-600 hover:border-rose-300">
                                Remove package
                            </button>
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ route('employee.functions.packages.update', [$functionEntry, $functionPackage]) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="crm-table-wrap">
                        <table class="crm-table min-w-[980px]">
                            <thead>
                                <tr>
                                    <th>Use</th>
                                    <th>Service</th>
                                    <th>Persons</th>
                                    <th>Rate</th>
                                    <th>Extra Charge</th>
                                    <th>Notes</th>
                                    <th>Row Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($functionPackage->serviceLines as $line)
                                    <tr x-data="{ persons: '{{ $line->persons }}', rate: '{{ number_format($line->rate_minor / 100, 2, '.', '') }}', extra: '{{ number_format($line->extra_charge_minor / 100, 2, '.', '') }}' }">
                                        <td>
                                            <input type="checkbox" name="service_lines[{{ $line->id }}][is_selected]" value="1" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked($line->is_selected) />
                                        </td>
                                        <td class="font-semibold text-slate-900">{{ $line->item_name_snapshot }}</td>
                                        <td>
                                            <x-text-input name="service_lines[{{ $line->id }}][persons]" x-model="persons" :value="old('service_lines.'.$line->id.'.persons', $line->persons)" class="crm-input w-full min-w-[6rem]" />
                                        </td>
                                        <td>
                                            <x-text-input name="service_lines[{{ $line->id }}][rate]" x-model="rate" :value="old('service_lines.'.$line->id.'.rate', Money::formatMinor($line->rate_minor))" class="crm-input w-full min-w-[7rem] bg-slate-100" readonly />
                                        </td>
                                        <td>
                                            <x-text-input name="service_lines[{{ $line->id }}][extra_charge]" x-model="extra" :value="old('service_lines.'.$line->id.'.extra_charge', Money::formatMinor($line->extra_charge_minor))" class="crm-input w-full min-w-[7rem]" />
                                        </td>
                                        <td>
                                            <textarea name="service_lines[{{ $line->id }}][notes]" rows="2" class="crm-input min-w-[14rem]">{{ old('service_lines.'.$line->id.'.notes', $line->notes) }}</textarea>
                                        </td>
                                        <td class="font-semibold text-slate-900" x-text="(((parseFloat(persons || 0) * parseFloat(rate || 0)) + parseFloat(extra || 0))).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></td>
                                    </tr>
                                @endforeach
                                <tr class="bg-cyan-50/70">
                                    <td colspan="6" class="font-semibold text-slate-900">Package Total</td>
                                    <td class="font-semibold text-slate-950">{{ Money::formatMinor($functionPackage->total_minor) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="crm-button crm-button-primary justify-center">Save package rows</button>
                    </div>
                </form>
            </article>
        @empty
            <article class="rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                No packages linked yet. Add one from the assigned package list above.
            </article>
        @endforelse
    </div>

    <div class="space-y-6" x-show="activeTab === 'extra-charges'" x-cloak>
        <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
            <p class="crm-section-title">Add extra charge</p>
            <form method="POST" action="{{ route('employee.functions.extra-charges.store', $functionEntry) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-3 lg:grid-cols-[11rem_1fr_11rem_10rem_auto]">
                    <x-text-input name="entry_date" type="date" :value="old('entry_date', optional($functionEntry->entry_date)->format('Y-m-d'))" class="crm-input w-full" />
                    <x-text-input name="name" :value="old('name')" class="crm-input w-full" placeholder="Charge name" />
                    <select name="mode" class="crm-input w-full">
                        @foreach ($modeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="amount" :value="old('amount')" class="crm-input w-full" placeholder="Amount" />
                    <button type="submit" class="crm-button crm-button-primary justify-center">Add</button>
                </div>
                <textarea name="note" rows="3" class="crm-input w-full" placeholder="Note">{{ old('note') }}</textarea>
                @include('employee.functions.partials.attachments', [
                    'attachable' => new \App\Models\FunctionExtraCharge(),
                    'functionEntry' => $functionEntry,
                    'inputId' => 'new_extra_charge_attachments',
                    'emptyMessage' => null,
                ])
            </form>
        </article>

        <div class="crm-table-wrap">
            <table class="crm-table min-w-[980px]">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Mode</th>
                        <th>Files</th>
                        <th>Notes</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($functionEntry->extraCharges as $record)
                        <tr>
                            <td>{{ optional($record->entry_date)->format('d M Y') }}</td>
                            <td class="font-semibold text-slate-950">{{ $record->name }}</td>
                            <td>{{ $modeOptions[$record->mode] ?? ucfirst((string) $record->mode) }}</td>
                            <td>{{ $record->attachments->count() }}</td>
                            <td>{{ $record->note ?: 'No note' }}</td>
                            <td>{{ Money::formatMinor($record->amount_minor) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No extra charges added yet.</td>
                        </tr>
                    @endforelse
                    @if ($functionEntry->extraCharges->isNotEmpty())
                        <tr class="bg-cyan-50/70">
                            <td colspan="5" class="font-semibold text-slate-900">Extra Charge Total</td>
                            <td class="font-semibold text-slate-950">{{ Money::formatMinor($functionEntry->extra_charge_total_minor) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($functionEntry->extraCharges->isNotEmpty())
            <div class="space-y-3">
                <p class="crm-section-title">Edit saved extra charges</p>
                @foreach ($functionEntry->extraCharges as $record)
                    <details class="crm-panel p-5">
                        <summary class="cursor-pointer list-none font-semibold text-slate-950">{{ $record->name }} | {{ Money::formatMinor($record->amount_minor) }}</summary>
                        <form method="POST" action="{{ route('employee.functions.extra-charges.update', [$functionEntry, $record]) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
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
                    </details>
                @endforeach
            </div>
        @endif
    </div>

    <div class="space-y-6" x-show="activeTab === 'installments'" x-cloak>
        <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
            <p class="crm-section-title">Add installment</p>
            <form method="POST" action="{{ route('employee.functions.installments.store', $functionEntry) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-3 lg:grid-cols-[11rem_1fr_11rem_10rem_auto]">
                    <x-text-input name="entry_date" type="date" :value="optional($functionEntry->entry_date)->format('Y-m-d')" class="crm-input w-full" />
                    <x-text-input name="name" class="crm-input w-full" placeholder="Installment name" />
                    <select name="mode" class="crm-input w-full">
                        @foreach ($modeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="amount" class="crm-input w-full" placeholder="Amount" />
                    <button type="submit" class="crm-button crm-button-primary justify-center">Add</button>
                </div>
                <textarea name="note" rows="3" class="crm-input w-full" placeholder="Note"></textarea>
                @include('employee.functions.partials.attachments', [
                    'attachable' => new \App\Models\FunctionInstallment(),
                    'functionEntry' => $functionEntry,
                    'inputId' => 'new_installment_attachments',
                    'emptyMessage' => null,
                ])
            </form>
        </article>

        <div class="crm-table-wrap">
            <table class="crm-table min-w-[980px]">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Mode</th>
                        <th>Files</th>
                        <th>Notes</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($functionEntry->installments as $record)
                        <tr>
                            <td>{{ optional($record->entry_date)->format('d M Y') }}</td>
                            <td class="font-semibold text-slate-950">{{ $record->name }}</td>
                            <td>{{ $modeOptions[$record->mode] ?? ucfirst((string) $record->mode) }}</td>
                            <td>{{ $record->attachments->count() }}</td>
                            <td>{{ $record->note ?: 'No note' }}</td>
                            <td>{{ Money::formatMinor($record->amount_minor) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No installments added yet.</td>
                        </tr>
                    @endforelse
                    @if ($functionEntry->installments->isNotEmpty())
                        <tr class="bg-cyan-50/70">
                            <td colspan="5" class="font-semibold text-slate-900">Installment Total</td>
                            <td class="font-semibold text-slate-950">{{ Money::formatMinor($functionEntry->paid_total_minor) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($functionEntry->installments->isNotEmpty())
            <div class="space-y-3">
                <p class="crm-section-title">Edit saved installments</p>
                @foreach ($functionEntry->installments as $record)
                    <details class="crm-panel p-5">
                        <summary class="cursor-pointer list-none font-semibold text-slate-950">{{ $record->name }} | {{ Money::formatMinor($record->amount_minor) }}</summary>
                        <form method="POST" action="{{ route('employee.functions.installments.update', [$functionEntry, $record]) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
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
                    </details>
                @endforeach
            </div>
        @endif
    </div>

    <div class="space-y-6" x-show="activeTab === 'discounts'" x-cloak>
        <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
            <p class="crm-section-title">Add discount</p>
            <form method="POST" action="{{ route('employee.functions.discounts.store', $functionEntry) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-3 lg:grid-cols-[11rem_1fr_11rem_10rem_auto]">
                    <x-text-input name="entry_date" type="date" :value="optional($functionEntry->entry_date)->format('Y-m-d')" class="crm-input w-full" />
                    <x-text-input name="name" class="crm-input w-full" placeholder="Discount name" />
                    <select name="mode" class="crm-input w-full">
                        @foreach ($modeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="amount" class="crm-input w-full" placeholder="Amount" />
                    <button type="submit" class="crm-button crm-button-primary justify-center">Add</button>
                </div>
                <textarea name="note" rows="3" class="crm-input w-full" placeholder="Note"></textarea>
                @include('employee.functions.partials.attachments', [
                    'attachable' => new \App\Models\FunctionDiscount(),
                    'functionEntry' => $functionEntry,
                    'inputId' => 'new_discount_attachments',
                    'emptyMessage' => null,
                ])
            </form>
        </article>

        <div class="crm-table-wrap">
            <table class="crm-table min-w-[980px]">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Mode</th>
                        <th>Files</th>
                        <th>Notes</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($functionEntry->discounts as $record)
                        <tr>
                            <td>{{ optional($record->entry_date)->format('d M Y') }}</td>
                            <td class="font-semibold text-slate-950">{{ $record->name }}</td>
                            <td>{{ $modeOptions[$record->mode] ?? ucfirst((string) $record->mode) }}</td>
                            <td>{{ $record->attachments->count() }}</td>
                            <td>{{ $record->note ?: 'No note' }}</td>
                            <td>{{ Money::formatMinor($record->amount_minor) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No discounts added yet.</td>
                        </tr>
                    @endforelse
                    @if ($functionEntry->discounts->isNotEmpty())
                        <tr class="bg-cyan-50/70">
                            <td colspan="5" class="font-semibold text-slate-900">Discount Total</td>
                            <td class="font-semibold text-slate-950">{{ Money::formatMinor($functionEntry->discount_total_minor) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($functionEntry->discounts->isNotEmpty())
            <div class="space-y-3">
                <p class="crm-section-title">Edit saved discounts</p>
                @foreach ($functionEntry->discounts as $record)
                    <details class="crm-panel p-5">
                        <summary class="cursor-pointer list-none font-semibold text-slate-950">{{ $record->name }} | {{ Money::formatMinor($record->amount_minor) }}</summary>
                        <form method="POST" action="{{ route('employee.functions.discounts.update', [$functionEntry, $record]) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
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
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</div>
