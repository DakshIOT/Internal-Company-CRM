<x-app-layout>
    <x-slot name="header">
        <div class="crm-toolbar">
            <div>
                <p class="crm-section-title">Admin Master Data</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Venues</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Manage venue contexts, vendor slots, and the employee workspaces that depend on them.
                </p>
            </div>
            <a href="{{ route('admin.master-data.venues.create') }}" class="crm-button crm-button-primary justify-center">
                Create venue
            </a>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <div class="space-y-6">
        <section class="crm-summary-grid">
            <article class="crm-kpi">
                <p class="crm-section-title">Total venues</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['total'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Active venues</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['active'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Assigned employees</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['assigned_employees'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Configured vendor slots</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['vendor_slots'] }}</p>
            </article>
        </section>

        <section class="crm-panel p-5">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_14rem_auto]">
                <x-text-input name="search" :value="$filters['search'] ?? ''" class="crm-input w-full" placeholder="Search by venue or code" />
                <select name="status" class="crm-input w-full">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active only</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive only</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="crm-button crm-button-secondary w-full justify-center">Filter</button>
                    <a href="{{ route('admin.master-data.venues.index') }}" class="crm-button crm-button-secondary w-full justify-center">Reset</a>
                </div>
            </form>
        </section>

        <section class="crm-panel overflow-hidden">
            <div class="crm-table-wrap rounded-none border-0">
                <table class="crm-table min-w-[980px]">
                    <thead>
                        <tr>
                            <th>Venue</th>
                            <th>Code</th>
                            <th>Assigned Users</th>
                            <th>Vendor Slots</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($venues as $venue)
                            <tr>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ $venue->name }}</p>
                                </td>
                                <td>{{ $venue->code ?: 'No code' }}</td>
                                <td>{{ $venue->users_count }}</td>
                                <td>{{ $venue->vendors_count ?? 4 }}</td>
                                <td>
                                    <span class="crm-chip {{ $venue->is_active ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $venue->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('admin.master-data.venues.edit', $venue) }}" class="crm-button crm-button-secondary">Edit</a>
                                        <form method="POST" action="{{ route('admin.master-data.venues.destroy', $venue) }}" onsubmit="return confirm('Delete this venue?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="crm-button border border-rose-200 bg-rose-50 text-rose-600 hover:border-rose-300">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">No venues found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{ $venues->links() }}
    </div>
</x-app-layout>
