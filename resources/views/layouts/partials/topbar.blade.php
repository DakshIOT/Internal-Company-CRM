@php
    $user = auth()->user();
@endphp

<header class="fixed inset-x-0 top-0 z-30 border-b border-white/50 bg-white/75 backdrop-blur-xl lg:left-72">
    <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8 xl:px-10">
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm lg:hidden"
                @click="sidebarOpen = true"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round" />
                </svg>
            </button>

            <div>
                <p class="crm-section-title">Workspace</p>
                <div class="mt-1">
                    @if (isset($header))
                        {{ $header }}
                    @else
                        <h1 class="font-display text-2xl font-semibold text-slate-950">Interior CRM</h1>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if (session('status'))
                <div class="hidden rounded-full bg-cyan-50 px-4 py-2 text-sm font-semibold text-cyan-700 md:block">
                    {{ session('status') }}
                </div>
            @endif

            @if ($user?->isEmployee())
                <div class="hidden sm:block">
                    <livewire:access.venue-switcher />
                </div>
            @else
                <div class="crm-chip bg-slate-950 text-white">Global Admin Context</div>
            @endif

            <div class="hidden rounded-2xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm md:block">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Signed in as</p>
                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $user?->name }}</p>
            </div>
        </div>
    </div>

    @if ($user?->isEmployee())
        <div class="border-t border-slate-100 px-4 py-3 sm:hidden">
            <livewire:access.venue-switcher />
        </div>
    @endif
</header>
