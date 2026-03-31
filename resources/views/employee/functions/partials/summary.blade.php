@php use App\Support\Money; @endphp

<div class="space-y-6">
    <article class="crm-panel p-6">
        <p class="crm-section-title">Entry totals</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Package Total</p>
                <p class="mt-2 text-xl font-semibold text-slate-950">{{ Money::formatMinor($functionEntry->package_total_minor) }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Extra Charges</p>
                <p class="mt-2 text-xl font-semibold text-slate-950">{{ Money::formatMinor($functionEntry->extra_charge_total_minor) }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Discounts</p>
                <p class="mt-2 text-xl font-semibold text-slate-950">{{ Money::formatMinor($functionEntry->discount_total_minor) }}</p>
            </div>
            <div class="rounded-[1.5rem] border border-cyan-200 bg-cyan-50 p-4">
                <p class="crm-section-title text-cyan-700">Function Total</p>
                <p class="mt-2 text-xl font-semibold text-cyan-950">{{ Money::formatMinor($functionEntry->function_total_minor) }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-white p-4 ring-1 ring-slate-100">
                <p class="crm-section-title">Paid</p>
                <p class="mt-2 text-xl font-semibold text-slate-950">{{ Money::formatMinor($functionEntry->paid_total_minor) }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-white p-4 ring-1 ring-slate-100">
                <p class="crm-section-title">Pending</p>
                <p class="mt-2 text-xl font-semibold text-slate-950">{{ Money::formatMinor($functionEntry->pending_total_minor) }}</p>
            </div>
        </div>

        @if ($functionEntry->frozen_fund_minor > 0)
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-[1.5rem] bg-cyan-50 p-4">
                    <p class="crm-section-title text-cyan-700">Frozen Fund</p>
                    <p class="mt-2 text-xl font-semibold text-cyan-900">{{ Money::formatMinor($functionEntry->frozen_fund_minor) }}</p>
                </div>
                <div class="rounded-[1.5rem] border border-sky-200 bg-sky-50 p-4">
                    <p class="crm-section-title text-sky-700">Net After Frozen Fund</p>
                    <p class="mt-2 text-xl font-semibold text-sky-950">{{ Money::formatMinor($functionEntry->net_total_after_frozen_fund_minor) }}</p>
                </div>
            </div>
        @endif
    </article>

    <article class="crm-panel p-6">
        <p class="crm-section-title">Date rollup</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Entries on {{ optional($functionEntry->entry_date)->format('d M Y') }}</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ $workspaceTotals['daily']['entry_count'] }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Date Function Total</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['daily']['function_total_minor']) }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Date Paid</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['daily']['paid_total_minor']) }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Date Pending</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['daily']['pending_total_minor']) }}</p>
            </div>
        </div>
    </article>

    <article class="crm-panel p-6">
        <p class="crm-section-title">Venue workspace totals</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Total entries</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ $workspaceTotals['grand']['entry_count'] }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Grand Function Total</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['function_total_minor']) }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Grand Paid</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['paid_total_minor']) }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-slate-50 p-4">
                <p class="crm-section-title">Grand Pending</p>
                <p class="mt-2 text-lg font-semibold text-slate-950">{{ Money::formatMinor($workspaceTotals['grand']['pending_total_minor']) }}</p>
            </div>
        </div>
    </article>
</div>
