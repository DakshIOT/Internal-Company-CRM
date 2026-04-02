<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="crm-section-title">Admin Master Data</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">{{ $isEditing ? 'Edit User Account' : 'Create User Account' }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Create the employee first, assign venue access here, and then finish package and service access inside the employee setup workspace.
            </p>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <form
        method="POST"
        action="{{ $isEditing ? route('admin.master-data.employees.update', $employee) : route('admin.master-data.employees.store') }}"
        class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]"
        x-data="{ role: '{{ old('role', $employee->role) }}' }"
    >
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <section class="space-y-6">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Identity</p>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="name" value="Full name" />
                        <x-text-input id="name" name="name" :value="old('name', $employee->name)" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email address" />
                        <x-text-input id="email" name="email" type="email" :value="old('email', $employee->email)" class="crm-input mt-2 w-full" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                </div>
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Access</p>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="role" value="Role" />
                        <select id="role" name="role" class="crm-input mt-2 w-full" x-model="role">
                            @foreach ($roleOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', $employee->role) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400" @checked(old('is_active', $employee->is_active ?? true))>
                            Keep this account active
                        </label>
                    </div>
                </div>

                <div class="mt-4 rounded-[1.25rem] bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                    <p x-show="role === '{{ \App\Support\Role::EMPLOYEE_A }}'">Employee Type A will see frozen fund per assigned venue and can use Function Entry, Daily Income, and Daily Billing.</p>
                    <p x-show="role === '{{ \App\Support\Role::EMPLOYEE_B }}'">Employee Type B will use the four vendor slots configured inside each assigned venue and can access Vendor Entry.</p>
                    <p x-show="role === '{{ \App\Support\Role::EMPLOYEE_C }}'">Employee Type C is function-only and does not use frozen fund or vendor-entry screens.</p>
                    <p x-show="role === '{{ \App\Support\Role::ADMIN }}'">Admin accounts stay global and do not need employee venue assignments.</p>
                </div>
            </article>

            <article class="crm-panel p-6" x-show="role !== '{{ \App\Support\Role::ADMIN }}'" x-cloak>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="crm-section-title">Venue access</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">Assign venues during employee setup</h2>
                    </div>
                    <span class="crm-chip bg-cyan-50 text-cyan-700">{{ count(old('venue_ids', $assignedVenueIds)) }} selected</span>
                </div>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    This is the first step in the setup flow. After save, the employee workspace will handle packages and services per selected venue.
                </p>

                <div class="mt-5 crm-table-wrap">
                    <table class="crm-table min-w-[760px]">
                        <thead>
                            <tr>
                                <th>Use</th>
                                <th>Venue</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th x-show="role === '{{ \App\Support\Role::EMPLOYEE_A }}'">Frozen Fund</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($venues as $venue)
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            name="venue_ids[]"
                                            value="{{ $venue->id }}"
                                            class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400"
                                            @checked(in_array($venue->id, old('venue_ids', $assignedVenueIds), true))
                                        >
                                    </td>
                                    <td class="font-semibold text-slate-950">{{ $venue->name }}</td>
                                    <td>{{ $venue->code ?: 'No code' }}</td>
                                    <td>{{ $venue->is_active ? 'Active' : 'Inactive' }}</td>
                                    <td x-show="role === '{{ \App\Support\Role::EMPLOYEE_A }}'">
                                        <x-text-input
                                            :id="'frozen_fund_'.$venue->id"
                                            :name="'frozen_funds['.$venue->id.']'"
                                            :value="old('frozen_funds.'.$venue->id, $frozenFunds[$venue->id] ?? '0.00')"
                                            class="crm-input w-32"
                                            placeholder="0.00"
                                        />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-sm text-slate-500">
                                        Create venues first, then come back to assign them to the employee.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-input-error :messages="$errors->get('venue_ids')" class="mt-3" />
                <x-input-error :messages="$errors->get('venue_ids.*')" class="mt-3" />
                <x-input-error :messages="$errors->get('frozen_funds.*')" class="mt-3" />
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Credentials</p>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <x-password-field
                            id="password"
                            name="password"
                            :label="$isEditing ? 'New password' : 'Password'"
                            :messages="$errors->get('password')"
                        />
                    </div>
                    <div>
                        <x-password-field
                            id="password_confirmation"
                            name="password_confirmation"
                            label="Confirm password"
                            :messages="$errors->get('password_confirmation')"
                        />
                    </div>
                </div>
                @if ($isEditing)
                    <p class="mt-3 text-sm text-slate-500">Leave both password fields empty to keep the existing password unchanged.</p>
                @endif
            </article>
        </section>

        <aside class="space-y-6">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Setup flow</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <li>1. Create the user and choose the employee type.</li>
                    <li>2. Assign venue access here.</li>
                    <li>3. If the employee is Type A, enter frozen fund per venue here.</li>
                    <li>4. Save and continue to the employee setup workspace for packages and services.</li>
                </ul>
            </article>

            @if ($isEditing)
                <article class="crm-panel p-6">
                    <p class="crm-section-title">Current linkage</p>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <p><span class="font-semibold text-slate-900">{{ $employee->venues_count }}</span> assigned venues</p>
                    </div>
                    @if ($employee->isEmployee())
                        <a href="{{ route('admin.master-data.employees.assignments.edit', $employee) }}" class="crm-button crm-button-secondary mt-5 w-full justify-center">Open employee setup workspace</a>
                    @endif
                </article>
            @endif

            <div class="flex flex-col gap-3">
                <button type="submit" data-loading-label="{{ $isEditing ? 'Saving...' : 'Creating...' }}" class="crm-button crm-button-primary justify-center">{{ $isEditing ? 'Save user' : 'Create user' }}</button>
                <a href="{{ route('admin.master-data.employees.index') }}" class="crm-button crm-button-secondary justify-center">Back to users</a>
            </div>
        </aside>
    </form>
</x-app-layout>
