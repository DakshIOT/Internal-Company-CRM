<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="crm-section-title">Admin Master Data</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Venues</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Manage venue contexts, active state, and the four vendor slots that Employee Type B will use later.
                </p>
            </div>
            <a href="{{ route('admin.master-data.venues.create') }}" class="crm-button crm-button-primary justify-center">
                Create venue
            </a>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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

        <section class="space-y-4 lg:hidden">
            @forelse ($venues as $venue)
                <article class="crm-panel p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="crm-section-title">Venue</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ $venue->name }}</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ $venue->code ?: 'No code' }}</p>
                        </div>
                        <span class="crm-chip {{ $venue->is_active ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $venue->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm text-slate-600">
                        <span>{{ $venue->users_count }} assigned users</span>
                        <a href="{{ route('admin.master-data.venues.edit', $venue) }}" class="font-semibold text-cyan-700">Edit</a>
                    </div>
                </article>
            @empty
                <article class="crm-panel p-8 text-center text-sm text-slate-500">
                    No venues found for the current filter.
                </article>
            @endforelse
        </section>

        <section class="crm-panel hidden overflow-hidden lg:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/90 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Venue</th>
                            <th class="px-6 py-4 font-semibold">Code</th>
                            <th class="px-6 py-4 font-semibold">Users</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white/80">
                        @forelse ($venues as $venue)
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900">{{ $venue->name }}</p>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $venue->code ?: 'No code' }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $venue->users_count }}</td>
                                <td class="px-6 py-4">
                                    <span class="crm-chip {{ $venue->is_active ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $venue->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
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
                                <td colspan="5" class="px-6 py-10 text-center text-slate-500">No venues found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{ $venues->links() }}
    </div>
</x-app-layout>
