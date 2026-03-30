<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="crm-section-title">Admin Master Data</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-slate-950">Packages</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Build reusable package shells and map the service rows that employees can use later in Function Entry.
                </p>
            </div>
            <a href="{{ route('admin.master-data.packages.create') }}" class="crm-button crm-button-primary justify-center">
                Create package
            </a>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-3">
            <article class="crm-kpi">
                <p class="crm-section-title">Total packages</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['total'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Active packages</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['active'] }}</p>
            </article>
            <article class="crm-kpi">
                <p class="crm-section-title">Mapped services</p>
                <p class="mt-4 font-display text-3xl font-semibold text-slate-950">{{ $stats['mapped_services'] }}</p>
            </article>
        </section>

        <section class="crm-panel p-5">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_14rem_auto]">
                <x-text-input name="search" :value="$filters['search'] ?? ''" class="crm-input w-full" placeholder="Search by package or code" />
                <select name="status" class="crm-input w-full">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active only</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive only</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="crm-button crm-button-secondary w-full justify-center">Filter</button>
                    <a href="{{ route('admin.master-data.packages.index') }}" class="crm-button crm-button-secondary w-full justify-center">Reset</a>
                </div>
            </form>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            @forelse ($packages as $package)
                <article class="crm-panel p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="crm-section-title">Package</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ $package->name }}</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ $package->code ?: 'No code' }}</p>
                        </div>
                        <span class="crm-chip {{ $package->is_active ? 'bg-cyan-50 text-cyan-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $package->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="mt-5 rounded-[1.5rem] bg-slate-50 p-4">
                        <p class="crm-section-title">Mapped services</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $package->services_count }}</p>
                    </div>
                    @if ($package->description)
                        <p class="mt-4 text-sm leading-6 text-slate-600">{{ $package->description }}</p>
                    @endif
                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <a href="{{ route('admin.master-data.packages.edit', $package) }}" class="crm-button crm-button-secondary">Edit mapping</a>
                        <form method="POST" action="{{ route('admin.master-data.packages.destroy', $package) }}" onsubmit="return confirm('Delete this package?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="crm-button border border-rose-200 bg-rose-50 text-rose-600 hover:border-rose-300">
                                Delete
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <article class="crm-panel p-8 text-center text-sm text-slate-500 xl:col-span-2">
                    No packages found for the current filter.
                </article>
            @endforelse
        </section>

        {{ $packages->links() }}
    </div>
</x-app-layout>
