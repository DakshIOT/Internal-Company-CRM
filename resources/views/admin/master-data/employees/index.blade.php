@php use App\Support\Role; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="crm-toolbar">
            <div>
                <p class="crm-section-title">Admin Master Data</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Employees and access</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Manage internal accounts, role assignment, and the venue assignment workspace each employee depends on.
                </p>
            </div>
            <a href="{{ route('admin.master-data.employees.create') }}" class="crm-button crm-button-primary justify-center">
                Create user
            </a>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <div class="space-y-6">
        <section class="crm-summary-grid">
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

        <section class="crm-panel overflow-hidden">
            <div class="crm-table-wrap rounded-none border-0">
                <table class="crm-table min-w-[1050px]">
                    <thead>
                        <tr><th>User</th><th>Role</th><th>Assigned Venues</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($employees as $employee)
                            <tr>
                                <td><p class="font-semibold text-slate-900">{{ $employee->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $employee->email }}</p></td>
                                <td><span class="crm-chip {{ $employee->role === Role::ADMIN ? 'bg-slate-950 text-white' : 'bg-cyan-50 text-cyan-700' }}">{{ $employee->roleLabel() }}</span></td>
                                <td>{{ $employee->venues_count }}</td>
                                <td><span class="crm-chip {{ $employee->is_active ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-500' }}">{{ $employee->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('admin.master-data.employees.edit', $employee) }}" class="crm-button crm-button-secondary">Edit</a>
                                        @if ($employee->isEmployee())
                                            <a href="{{ route('admin.master-data.employees.assignments.edit', $employee) }}" class="crm-button crm-button-secondary">Setup</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{ $employees->links() }}
    </div>
</x-app-layout>
