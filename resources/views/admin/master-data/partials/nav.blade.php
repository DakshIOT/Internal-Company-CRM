<nav class="mb-6 space-y-4">
    <div class="crm-scroll-strip">
        <div class="inline-flex min-w-max gap-2 rounded-[1.75rem] border border-white/60 bg-white/70 p-2 shadow-[0_18px_50px_-32px_rgba(15,23,42,0.35)] backdrop-blur-xl">
            @foreach ([
                ['label' => 'Employees', 'route' => 'admin.master-data.employees.index'],
                ['label' => 'Venues', 'route' => 'admin.master-data.venues.index'],
                ['label' => 'Services', 'route' => 'admin.master-data.services.index'],
                ['label' => 'Packages', 'route' => 'admin.master-data.packages.index'],
                ['label' => 'Print Settings', 'route' => 'admin.master-data.function-print-settings.edit'],
            ] as $item)
                <a
                    href="{{ route($item['route']) }}"
                    class="inline-flex items-center rounded-2xl px-4 py-2 text-sm font-semibold transition {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'bg-slate-950 text-white shadow-lg shadow-slate-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="crm-panel p-4 sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="crm-section-title">Admin Setup Guide</p>
                <h2 class="mt-2 text-xl font-semibold text-slate-950">Recommended order</h2>
            </div>
            <span class="crm-chip bg-cyan-50 text-cyan-700">Employee B only: Vendor Entry + 4 venue vendors</span>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                '1. Create the venue first. Each venue keeps its own 4 vendor slots.',
                '2. Create the employee account and choose the correct employee type.',
                '3. Open employee assignments and turn on the required venue for that employee.',
                '4. Inside that venue, select only the allowed services and packages for that employee.',
                '5. Use admin reports to filter by employee or venue before exporting the final data.',
            ] as $step)
                <div class="rounded-[1.25rem] border border-slate-100 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                    {{ $step }}
                </div>
            @endforeach
        </div>
    </div>
</nav>
