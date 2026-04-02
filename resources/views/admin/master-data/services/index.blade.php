@php use App\Support\Money; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="crm-toolbar">
            <div>
                <p class="crm-section-title">Admin Master Data</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Services</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Define reusable services and their standard rates for venue assignment and package mapping.
                </p>
            </div>
            <a href="{{ route('admin.master-data.services.create') }}" class="crm-button crm-button-primary justify-center">
                Create service
            </a>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-3">
            <article class="crm-kpi">
                <p class="crm-section-title">Total services</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['total'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Active services</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['active'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Assigned services</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['assigned'] }}</p>
            </article>
        </section>

        <section class="crm-panel p-5">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_14rem_auto]">
                <x-text-input name="search" :value="$filters['search'] ?? ''" class="crm-input w-full" placeholder="Search by service or code" />
                <select name="status" class="crm-input w-full">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active only</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive only</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="crm-button crm-button-secondary w-full justify-center">Filter</button>
                    <a href="{{ route('admin.master-data.services.index') }}" class="crm-button crm-button-secondary w-full justify-center">Reset</a>
                </div>
            </form>
        </section>

        <section class="crm-panel overflow-hidden">
            <div class="crm-table-wrap rounded-none border-0">
                <table class="crm-table min-w-[1080px]">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Code</th>
                            <th>Rate</th>
                            <th>Assigned Packages</th>
                            <th>Assignments</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($services as $service)
                            <tr>
                                <td class="font-semibold text-slate-950">{{ $service->name }}</td>
                                <td>{{ $service->code ?: 'No code' }}</td>
                                <td>{{ Money::formatMinor($service->standard_rate_minor) }}</td>
                                <td>
                                    @if ($service->packages->isNotEmpty())
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($service->packages->take(3) as $package)
                                                <span class="crm-chip bg-slate-100 text-slate-600">{{ $package->name }}</span>
                                            @endforeach
                                            @if ($service->packages_count > 3)
                                                <span class="crm-chip bg-white text-slate-500">+{{ $service->packages_count - 3 }} more</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-500">No packages</span>
                                    @endif
                                </td>
                                <td>{{ $service->assignments_count }}</td>
                                <td>
                                    <span class="crm-chip {{ $service->is_active ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $service->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('admin.master-data.services.edit', $service) }}" class="crm-button crm-button-secondary">Edit</a>
                                        <form method="POST" action="{{ route('admin.master-data.services.destroy', $service) }}" onsubmit="return confirm('Delete this service?');">
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
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                    No services found for the current filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{ $services->links() }}
    </div>
</x-app-layout>
