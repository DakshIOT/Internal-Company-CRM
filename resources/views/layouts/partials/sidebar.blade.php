@php
    use App\Support\Role;

    $user = auth()->user();
    $liveModules = ['Function Entry', 'Daily Income', 'Daily Billing', 'Vendor Entry', 'Admin Income'];
    $navigation = $user?->isAdmin()
        ? [
            ['label' => 'Admin Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
            ['label' => 'Reports', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*'],
            ['label' => 'Admin Income', 'route' => 'admin.admin-income.index', 'active' => 'admin.admin-income.*'],
            ['label' => 'Venues', 'route' => 'admin.master-data.venues.index', 'active' => 'admin.master-data.venues.*'],
            ['label' => 'Employees', 'route' => 'admin.master-data.employees.index', 'active' => 'admin.master-data.employees.*'],
            ['label' => 'Services', 'route' => 'admin.master-data.services.index', 'active' => 'admin.master-data.services.*'],
            ['label' => 'Packages', 'route' => 'admin.master-data.packages.index', 'active' => 'admin.master-data.packages.*'],
            ['label' => 'Print Settings', 'route' => 'admin.master-data.function-print-settings.edit', 'active' => 'admin.master-data.function-print-settings.*'],
            ['label' => 'Profile', 'route' => 'profile.edit', 'active' => 'profile.edit'],
        ]
        : array_values(array_filter([
            ['label' => 'Employee Dashboard', 'route' => 'employee.dashboard', 'active' => 'employee.dashboard'],
            ['label' => 'Function Entry', 'route' => 'employee.functions.index', 'active' => 'employee.functions.*'],
            $user?->hasRole([Role::EMPLOYEE_A, Role::EMPLOYEE_B])
                ? ['label' => 'Daily Income', 'route' => 'employee.daily-income.index', 'active' => 'employee.daily-income.*']
                : null,
            $user?->hasRole([Role::EMPLOYEE_A, Role::EMPLOYEE_B])
                ? ['label' => 'Daily Billing', 'route' => 'employee.daily-billing.index', 'active' => 'employee.daily-billing.*']
                : null,
            $user?->hasRole(Role::EMPLOYEE_B)
                ? ['label' => 'Vendor Entry', 'route' => 'employee.vendor-entries.index', 'active' => 'employee.vendor-entries.*']
                : null,
            ['label' => 'Venue Selection', 'route' => 'venues.select', 'active' => 'venues.*'],
            ['label' => 'Profile', 'route' => 'profile.edit', 'active' => 'profile.edit'],
        ]));
@endphp

<div class="flex h-full min-h-full flex-col">
    <div class="flex items-center gap-4 border-b border-white/10 px-6 py-5">
        <x-application-logo class="h-12 w-12" />
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Interior CRM</p>
            <p class="mt-1 text-sm font-medium text-slate-300">{{ $user?->roleLabel() }}</p>
        </div>
    </div>

    <div class="px-4 pt-4 lg:px-4 lg:pt-4 xl:px-6 xl:pt-5">
        <div class="crm-glass-panel hidden space-y-2 xl:block">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Foundation</p>
            <p class="font-display text-lg font-semibold text-white">{{ $user?->isAdmin() ? 'Phase 5 reporting live' : 'Phase 4 employee ledgers' }}</p>
            <p class="text-sm leading-6 text-slate-300">
                {{ $user?->isAdmin()
                    ? 'Admin dashboard, report pages, and Excel exports now read from explicit filter contracts and server-side totals.'
                    : 'Function Entry, Daily Income, Daily Billing, and Vendor Entry now share the same venue-scoped workflow and attachment rules.' }}
            </p>
        </div>
    </div>

    <nav class="crm-sidebar-scroll mt-4 flex-1 overflow-y-auto px-3 pb-5 lg:px-3 xl:mt-5 xl:px-4 xl:pb-6">
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

        <div class="mt-5 rounded-[1.5rem] border border-white/10 bg-white/5 p-3.5 xl:mt-6 xl:p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Approved modules</p>
            <ul class="mt-4 space-y-3 text-sm text-slate-300">
                @foreach (Role::modulesFor($user->role) as $module)
                    <li class="flex items-center justify-between gap-3">
                        <span>{{ $module }}</span>
                        <span class="crm-chip bg-white/10 text-slate-300">{{ in_array($module, $liveModules, true) ? 'Live' : 'Planned' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>

    <div class="border-t border-white/10 px-4 py-4 xl:px-6">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="crm-button w-full justify-center border border-white/10 bg-white/5 text-sm font-semibold text-slate-100 hover:bg-white/10">
                Log out
            </button>
        </form>
    </div>
</div>
