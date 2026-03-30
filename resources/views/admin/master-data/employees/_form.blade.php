@php
    $isEdit = $employee->exists;
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.master-data.employees.update', $employee) : route('admin.master-data.employees.store') }}" class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <article class="crm-panel p-6">
            <p class="crm-section-title">Identity</p>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="space-y-2">
                    <x-input-label for="name" value="Full name" />
                    <x-text-input id="name" name="name" class="crm-input block w-full" :value="old('name', $employee->name)" required />
                    <x-input-error :messages="$errors->get('name')" />
                </div>

                <div class="space-y-2">
                    <x-input-label for="email" value="Email address" />
                    <x-text-input id="email" name="email" type="email" class="crm-input block w-full" :value="old('email', $employee->email)" required />
                    <x-input-error :messages="$errors->get('email')" />
                </div>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div class="space-y-2">
                    <x-input-label for="role" value="Role" />
                    <select id="role" name="role" class="crm-input block w-full">
                        @foreach ($roleOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $employee->role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" />
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" @checked(old('is_active', $employee->is_active ?? true))>
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">Active account</span>
                        <span class="block text-sm text-slate-600">Inactive users cannot authenticate into the CRM.</span>
                    </span>
                </label>
            </div>
        </article>

        <article class="crm-panel p-6">
            <p class="crm-section-title">Credentials</p>
            <div class="mt-6 space-y-5">
                <div class="space-y-2">
                    <x-input-label for="password" :value="$isEdit ? 'New password (optional)' : 'Password'" />
                    <x-text-input id="password" name="password" type="password" class="crm-input block w-full" :required="! $isEdit" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                <div class="space-y-2">
                    <x-input-label for="password_confirmation" value="Confirm password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="crm-input block w-full" :required="! $isEdit" />
                </div>
            </div>

            <div class="mt-6 rounded-[1.5rem] bg-slate-950 p-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Admin-only control</p>
                <p class="mt-3 text-sm leading-6 text-slate-300">
                    Employees do not self-manage their role, venue assignments, or core identity. Access mapping stays on a separate admin screen.
                </p>
            </div>
        </article>
    </section>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm leading-6 text-slate-600">
            Save the account first, then configure venue, service, and package access on the dedicated access screen.
        </p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.master-data.employees.index') }}" class="crm-button crm-button-secondary">Back to employees</a>
            <button type="submit" class="crm-button crm-button-primary">{{ $isEdit ? 'Save employee changes' : 'Create employee' }}</button>
        </div>
    </div>
</form>
