@php
    use App\Support\Reports\ReportModule;

    $tabItems = [
        ['module' => ReportModule::FUNCTIONS, 'route' => ReportModule::routeName(ReportModule::FUNCTIONS)],
        ['module' => ReportModule::DAILY_INCOME, 'route' => ReportModule::routeName(ReportModule::DAILY_INCOME)],
        ['module' => ReportModule::DAILY_BILLING, 'route' => ReportModule::routeName(ReportModule::DAILY_BILLING)],
        ['module' => ReportModule::VENDOR_ENTRIES, 'route' => ReportModule::routeName(ReportModule::VENDOR_ENTRIES)],
        ['module' => ReportModule::ADMIN_INCOME, 'route' => ReportModule::routeName(ReportModule::ADMIN_INCOME)],
    ];
@endphp

<section class="crm-scroll-strip">
    <div class="flex min-w-max gap-2 sm:gap-3">
        <a href="{{ route('admin.dashboard', $filters->query()) }}" class="crm-tab {{ request()->routeIs('admin.dashboard') ? 'crm-tab-active' : '' }}">
            Dashboard
        </a>
        <a href="{{ route('admin.reports.index', $filters->query()) }}" class="crm-tab {{ request()->routeIs('admin.reports.index') ? 'crm-tab-active' : '' }}">
            Report Hub
        </a>

        @foreach ($tabItems as $item)
            <a
                href="{{ route($item['route'], $filters->query()) }}"
                class="crm-tab {{ ($module ?? null) === $item['module'] ? 'crm-tab-active' : '' }}"
            >
                {{ ReportModule::label($item['module']) }}
            </a>
        @endforeach
    </div>
</section>
