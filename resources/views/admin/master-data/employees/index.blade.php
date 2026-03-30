@php use App\Support\Role; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="crm-section-title">Admin Master Data</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Employees and access</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Manage internal accounts, role assignment, active state, and the gateway into venue-based workflows.
                </p>
            </div>
            <a href="{{ route('admin.master-data.employees.create') }}" class="crm-button crm-button-primary justify-center">
                Create user
            </a>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="crm-kpi"><p class="crm-section-title">Total users</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['total'] }}</p></article>
            <article class="crm-kpi"><p class="crm-section-title">Active users</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['active'] }}</p></article>
            <article class="crm-kpi"><p class="crm-section-title">Admins</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['admins'] }}</p></article>
            <article class="crm-kpi"><p class="crm-section-title">Employees</p><p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['employees'] }}</p></article>
        </section>

        <section class="crm-panel p-5">
            <form method="GET" class="grid gap-3 xl:grid-cols-[1fr_14rem_14rem_auto]">
                <x-text-input name="search" :value="$filters['search'] ?? ''" class="crm-input w-full" placeholder="Search by name or email" />
                <select name="role" class="crm-input w-full">
                    <option value="">All roles</option>
                    @foreach ($roleOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" class="crm-input w-full">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active only</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive only</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="crm-button crm-button-secondary w-full justify-center">Filter</button>
                    <a href="{{ route('admin.master-data.employees.index') }}" class="crm-button crm-button-secondary w-full justify-center">Reset</a>
                </div>
            </form>
        </section>

        <section class="space-y-4 lg:hidden">
            @forelse ($employees as $employee)
                <article class="crm-panel p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-950">{{ $employee->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $employee->email }}</p>
                        </div>
                        <span class="crm-chip {{ $employee->is_active ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-500' }}">{{ $employee->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm text-slate-600">
                        <span>{{ $employee->roleLabel() }} | {{ $employee->venues_count }} venues</span>
                        <div class="flex gap-3">
                            <a href="{{ route('admin.master-data.employees.edit', $employee) }}" class="font-semibold text-cyan-700">Edit</a>
                            @if ($employee->isEmployee())
                                <a href="{{ route('admin.master-data.employees.assignments.edit', $employee) }}" class="font-semibold text-slate-900">Assignments</a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <article class="crm-panel p-8 text-center text-sm text-slate-500">No users found for the current filter.</article>
            @endforelse
        </section>

        <section class="crm-panel hidden overflow-hidden lg:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/90 text-left text-slate-500">
                        <tr><th class="px-6 py-4 font-semibold">User</th><th class="px-6 py-4 font-semibold">Role</th><th class="px-6 py-4 font-semibold">Assigned venues</th><th class="px-6 py-4 font-semibold">Status</th><th class="px-6 py-4 font-semibold text-right">Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white/80">
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="px-6 py-4"><p class="font-semibold text-slate-900">{{ $employee->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $employee->email }}</p></td>
                                <td class="px-6 py-4"><span class="crm-chip {{ $employee->role === Role::ADMIN ? 'bg-slate-950 text-white' : 'bg-cyan-50 text-cyan-700' }}">{{ $employee->roleLabel() }}</span></td>
                                <td class="px-6 py-4 text-slate-600">{{ $employee->venues_count }}</td>
                                <td class="px-6 py-4"><span class="crm-chip {{ $employee->is_active ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-500' }}">{{ $employee->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.master-data.employees.edit', $employee) }}" class="crm-button crm-button-secondary">Edit</a>
                                        @if ($employee->isEmployee())
                                            <a href="{{ route('admin.master-data.employees.assignments.edit', $employee) }}" class="crm-button crm-button-secondary">Assignments</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{ $employees->links() }}
    </div>
</x-app-layout>
