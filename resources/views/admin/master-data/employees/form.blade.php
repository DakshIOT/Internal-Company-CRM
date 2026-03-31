<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="crm-section-title">Admin Master Data</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">{{ $isEditing ? 'Edit User Account' : 'Create User Account' }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Internal credentials remain admin-controlled. Employees do not manage their own role, identity, or activation state.
            </p>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <form method="POST" action="{{ $isEditing ? route('admin.master-data.employees.update', $employee) : route('admin.master-data.employees.store') }}" class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <section class="space-y-6">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Identity</p>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div><x-input-label for="name" value="Full name" /><x-text-input id="name" name="name" :value="old('name', $employee->name)" class="crm-input mt-2 w-full" /><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
                    <div><x-input-label for="email" value="Email address" /><x-text-input id="email" name="email" type="email" :value="old('email', $employee->email)" class="crm-input mt-2 w-full" /><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
                </div>
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Access</p>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="role" value="Role" />
                        <select id="role" name="role" class="crm-input mt-2 w-full">
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
            </article>

            <article class="crm-panel p-6">
                <p class="crm-section-title">Credentials</p>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div><x-input-label for="password" :value="$isEditing ? 'New password' : 'Password'" /><x-text-input id="password" name="password" type="password" class="crm-input mt-2 w-full" /><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
                    <div><x-input-label for="password_confirmation" value="Confirm password" /><x-text-input id="password_confirmation" name="password_confirmation" type="password" class="crm-input mt-2 w-full" /></div>
                </div>
                @if ($isEditing)
                    <p class="mt-3 text-sm text-slate-500">Leave both password fields empty to keep the existing password unchanged.</p>
                @endif
            </article>
        </section>

        <aside class="space-y-6">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Workflow</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <li>Create or update the user account first.</li>
                    <li>Then open the access workspace to choose venues, services, packages, and frozen fund values.</li>
                    <li>Frozen fund becomes relevant only for Employee Type A.</li>
                </ul>
            </article>

            @if ($isEditing)
                <article class="crm-panel p-6">
                    <p class="crm-section-title">Current linkage</p>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <p><span class="font-semibold text-slate-900">{{ $employee->venues_count }}</span> assigned venues</p>
                    </div>
                    @if ($employee->isEmployee())
                        <a href="{{ route('admin.master-data.employees.assignments.edit', $employee) }}" class="crm-button crm-button-secondary mt-5 w-full justify-center">Open assignment workspace</a>
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
