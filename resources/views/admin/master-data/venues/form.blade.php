<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="crm-section-title">Admin Master Data</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">
                {{ $isEditing ? 'Edit Venue' : 'Create Venue' }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Keep venue identity clean, maintain the four vendor slots here, and optionally attach employees to this venue before opening the employee setup workspace.
            </p>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <form method="POST" action="{{ $isEditing ? route('admin.master-data.venues.update', $venue) : route('admin.master-data.venues.store') }}" class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <section class="space-y-6">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Venue identity</p>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="name" value="Venue name" />
                        <x-text-input id="name" name="name" :value="old('name', $venue->name)" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="code" value="Venue code" />
                        <x-text-input id="code" name="code" :value="old('code', $venue->code)" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>
                </div>

                <label class="mt-6 inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked(old('is_active', $venue->is_active ?? true))>
                    Keep this venue active for assignments and dashboards
                </label>
            </article>

            <article class="crm-panel p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="crm-section-title">Vendor slots</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Exactly four names per venue</h2>
                    </div>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">Employee Type B only</span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach (range(1, 4) as $slotNumber)
                        <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-4">
                            <p class="crm-section-title">Vendor {{ $slotNumber }}</p>
                            <x-text-input
                                :id="'vendor-slot-'.$slotNumber"
                                :name="'vendor_slots['.$slotNumber.']'"
                                :value="old('vendor_slots.'.$slotNumber, $vendorSlots[$slotNumber])"
                                class="crm-input mt-3 w-full"
                            />
                        </div>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('vendor_slots.*')" class="mt-3" />
            </article>

            <article class="crm-panel p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="crm-section-title">Employee access</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Assign employees to this venue</h2>
                    </div>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ count(old('employee_ids', $selectedEmployeeIds)) }} selected</span>
                </div>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    This connects the employee to the venue only. Packages and service access still belong in the employee setup workspace so admin has one place to manage what each person can use.
                </p>

                <div class="mt-5 crm-table-wrap">
                    <table class="crm-table min-w-[760px]">
                        <thead>
                            <tr>
                                <th>Use</th>
                                <th>Employee</th>
                                <th>Email</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($employees as $employeeOption)
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            name="employee_ids[]"
                                            value="{{ $employeeOption->id }}"
                                            class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                            @checked(in_array($employeeOption->id, old('employee_ids', $selectedEmployeeIds), true))
                                        >
                                    </td>
                                    <td class="font-semibold text-slate-950">{{ $employeeOption->name }}</td>
                                    <td>{{ $employeeOption->email }}</td>
                                    <td>{{ $employeeOption->roleLabel() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-sm text-slate-500">
                                        Create employee accounts first, then come back to connect them to this venue.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-input-error :messages="$errors->get('employee_ids')" class="mt-3" />
                <x-input-error :messages="$errors->get('employee_ids.*')" class="mt-3" />
            </article>
        </section>

        <aside class="space-y-6">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Operational notes</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <li>Employees only see data from their selected venue context.</li>
                    <li>Vendor names configured here only feed Employee Type B vendor-entry screens.</li>
                    <li>Assign venues, packages, and services from the employee setup workspace to keep the workflow clear.</li>
                </ul>
            </article>

            @if ($isEditing)
                <article class="crm-panel p-6">
                    <p class="crm-section-title">Current linkage</p>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <p><span class="font-semibold text-slate-900">{{ $venue->users_count }}</span> assigned users</p>
                    </div>
                </article>
            @endif

            <div class="flex flex-col gap-3">
                <button type="submit" data-loading-label="{{ $isEditing ? 'Saving...' : 'Creating...' }}" class="crm-button crm-button-primary justify-center">
                    {{ $isEditing ? 'Save venue' : 'Create venue' }}
                </button>
                <a href="{{ route('admin.master-data.venues.index') }}" class="crm-button crm-button-secondary justify-center">
                    Back to venues
                </a>
            </div>
        </aside>
    </form>
</x-app-layout>
