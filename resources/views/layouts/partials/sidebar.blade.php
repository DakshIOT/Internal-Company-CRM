@php
    use App\Support\Role;

    $user = auth()->user();
    $navigation = $user?->isAdmin()
        ? [
            ['label' => 'Admin Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
            ['label' => 'Venues', 'route' => 'admin.master-data.venues.index', 'active' => 'admin.master-data.venues.*'],
            ['label' => 'Employees', 'route' => 'admin.master-data.employees.index', 'active' => 'admin.master-data.employees.*'],
            ['label' => 'Services', 'route' => 'admin.master-data.services.index', 'active' => 'admin.master-data.services.*'],
            ['label' => 'Packages', 'route' => 'admin.master-data.packages.index', 'active' => 'admin.master-data.packages.*'],
            ['label' => 'Profile', 'route' => 'profile.edit', 'active' => 'profile.edit'],
        ]
        : [
            ['label' => 'Employee Dashboard', 'route' => 'employee.dashboard', 'active' => 'employee.dashboard'],
            ['label' => 'Function Entry', 'route' => 'employee.functions.index', 'active' => 'employee.functions.*'],
            ['label' => 'Venue Selection', 'route' => 'venues.select', 'active' => 'venues.*'],
            ['label' => 'Profile', 'route' => 'profile.edit', 'active' => 'profile.edit'],
        ];
@endphp

<div class="flex h-full flex-col">
    <div class="flex items-center gap-4 border-b border-white/10 px-6 py-6">
        <x-application-logo class="h-12 w-12" />
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Interior CRM</p>
            <p class="mt-1 text-sm font-medium text-slate-300">{{ $user?->roleLabel() }}</p>
        </div>
    </div>

    <div class="px-6 pt-6">
        <div class="crm-glass-panel space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Foundation</p>
            <p class="font-display text-lg font-semibold text-white">{{ $user?->isAdmin() ? 'Phase 2 scaffold' : 'Phase 3 function workspace' }}</p>
            <p class="text-sm leading-6 text-slate-300">
                {{ $user?->isAdmin()
                    ? 'Authentication, role gates, venue context, and the master-data control layer are ready for module work.'
                    : 'Function Entry is live with a staged action center, live totals, and strict venue scoping for employee work.' }}
            </p>
        </div>
    </div>

    <nav class="mt-6 flex-1 px-4">
        <div class="space-y-2">
            @foreach ($navigation as $item)
                <a
                    href="{{ route($item['route']) }}"
                    class="crm-sidebar-link {{ request()->routeIs($item['active']) ? 'crm-sidebar-link-active' : '' }}"
                >
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white/10 text-xs font-bold text-cyan-200">
                        {{ strtoupper(substr($item['label'], 0, 1)) }}
                    </span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="mt-8 rounded-[1.5rem] border border-white/10 bg-white/5 p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Approved modules</p>
            <ul class="mt-4 space-y-3 text-sm text-slate-300">
                @foreach (Role::modulesFor($user->role) as $module)
                    <li class="flex items-center justify-between gap-3">
                        <span>{{ $module }}</span>
                        <span class="crm-chip bg-white/10 text-slate-300">{{ $user->isAdmin() ? 'Ready' : 'Planned' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>

    <div class="border-t border-white/10 px-6 py-5">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="crm-button w-full justify-center border border-white/10 bg-white/5 text-sm font-semibold text-slate-100 hover:bg-white/10">
                Log out
            </button>
        </form>
    </div>
</div>
