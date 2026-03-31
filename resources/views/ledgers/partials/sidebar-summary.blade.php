@php use App\Support\Money; @endphp

<div class="space-y-6">
    <article class="crm-panel p-6">
        <p class="crm-section-title">Current entry</p>
        <h2 class="mt-3 text-2xl font-semibold text-slate-950">{{ $entry->name ?: 'New entry' }}</h2>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Entry amount</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ Money::formatMinor((int) ($entry->amount_minor ?? 0)) }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">{{ $workspaceTotals['focus']['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['focus']['amount_minor']) }}</p>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $workspaceTotals['focus']['entry_date'] ? \Illuminate\Support\Carbon::parse($workspaceTotals['focus']['entry_date'])->format('d M Y') : 'No date selected' }}
                </p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Grand total</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['amount_minor']) }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $workspaceTotals['grand']['entry_count'] }} entries in scope</p>
            </div>
        </div>
    </article>

    @if (! empty($workspaceTotals['vendor_totals']) && count($workspaceTotals['vendor_totals']) > 0)
        <article class="crm-panel p-6">
            <p class="crm-section-title">Vendor totals</p>
            <div class="mt-4 space-y-3">
                @foreach ($workspaceTotals['vendor_totals'] as $vendorTotal)
                    <div class="rounded-[1.25rem] border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold text-slate-900">{{ $vendorTotal['vendor_name'] }}</span>
                            <span class="crm-chip bg-white text-slate-500">{{ $vendorTotal['entry_count'] }} rows</span>
                        </div>
                        <p class="mt-3 text-lg font-semibold text-slate-950">{{ Money::formatMinor($vendorTotal['amount_minor']) }}</p>
                    </div>
                @endforeach
            </div>
        </article>
    @endif

    <article class="crm-panel p-6">
        <p class="crm-section-title">Recent date totals</p>
        <div class="mt-4 space-y-3">
            @forelse ($workspaceTotals['date_totals'] as $dateTotal)
                <div class="rounded-[1.25rem] border border-slate-100 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold text-slate-900">{{ \Illuminate\Support\Carbon::parse($dateTotal['entry_date'])->format('d M Y') }}</span>
                        <span class="crm-chip bg-white text-slate-500">{{ $dateTotal['entry_count'] }} rows</span>
                    </div>
                    <p class="mt-3 text-lg font-semibold text-slate-950">{{ Money::formatMinor($dateTotal['amount_minor']) }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No date totals available yet.</p>
            @endforelse
        </div>
    </article>
</div>
