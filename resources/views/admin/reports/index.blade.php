<x-app-layout>
    <x-slot name="header">
        <div class="crm-toolbar">
            <div>
                <p class="crm-section-title">Admin Reports</p>
                <h1 class="font-display text-3xl font-semibold text-slate-950">Report hub</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Pick the employee, venue, role, or date scope once, then open the exact report or export you need.
                </p>
            </div>
            <a href="{{ route('admin.dashboard', $filters->query()) }}" class="crm-button crm-button-secondary justify-center">
                Back to report dashboard
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @include('admin.reports.partials.module-tabs', ['filters' => $filters])

        @include('admin.reports.partials.filter-card', [
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'showModule' => true,
            'supportsVenue' => true,
            'supportsVendor' => true,
            'supportsPackageService' => true,
            'resetRoute' => route('admin.reports.index'),
        ])

        <section class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($reportLinks as $link)
                <article class="crm-panel p-6">
                    <p class="crm-section-title">{{ $link['label'] }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $link['description'] }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route($link['route'], $link['query']) }}" class="crm-button crm-button-primary justify-center">
                            Open report
                        </a>
                        @php $exportRoute = str_replace('.index', '.export', $link['route']); @endphp
                        @if (\Illuminate\Support\Facades\Route::has($exportRoute))
                            <a href="{{ route($exportRoute, $link['query']) }}" class="crm-button crm-button-secondary justify-center">
                                Export
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>
    </div>
</x-app-layout>
