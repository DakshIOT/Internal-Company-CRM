<nav class="mb-6 overflow-x-auto">
    <div class="inline-flex min-w-full gap-2 rounded-[1.75rem] border border-white/60 bg-white/70 p-2 shadow-[0_18px_50px_-32px_rgba(15,23,42,0.35)] backdrop-blur-xl">
        @foreach ([
            ['label' => 'Employees', 'route' => 'admin.master-data.employees.index'],
            ['label' => 'Venues', 'route' => 'admin.master-data.venues.index'],
            ['label' => 'Services', 'route' => 'admin.master-data.services.index'],
            ['label' => 'Packages', 'route' => 'admin.master-data.packages.index'],
        ] as $item)
            <a
                href="{{ route($item['route']) }}"
                class="inline-flex items-center rounded-2xl px-4 py-2 text-sm font-semibold transition {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'bg-slate-950 text-white shadow-lg shadow-slate-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}"
            >
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
