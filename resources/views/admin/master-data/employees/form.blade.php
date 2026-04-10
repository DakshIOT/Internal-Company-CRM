<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="crm-section-title">Admin Master Data</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">{{ $isEditing ? 'Edit User Account' : 'Create User Account' }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Keep account creation simple here. Venue, package, and service access now live in the dedicated employee setup workspace.
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
                    <li>2. Open the employee setup workspace from the employee list or edit screen.</li>
                    <li>3. Add or assign venues for that employee one by one.</li>
                    <li>4. Inside each venue, add or assign packages and then services inside the selected package.</li>
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
            @else
                <article class="crm-panel p-6" x-show="role !== '{{ \App\Support\Role::ADMIN }}'" x-cloak>
                    <p class="crm-section-title">After save</p>
                    <p class="mt-4 text-sm leading-6 text-slate-600">
                        As soon as this employee is created, the next screen will open the dedicated setup workspace so you can assign venues, packages, and package services without leaving the employee context.
                    </p>
                </article>
            @endif

            <div class="flex flex-col gap-3">
                <button type="submit" data-loading-label="{{ $isEditing ? 'Saving...' : 'Creating...' }}" class="crm-button crm-button-primary justify-center">{{ $isEditing ? 'Save user' : 'Create user' }}</button>
                <a href="{{ route('admin.master-data.employees.index') }}" class="crm-button crm-button-secondary justify-center">Back to users</a>
            </div>
        </aside>
    </form>
</x-app-layout>
