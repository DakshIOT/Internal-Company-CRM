@php
    $user = auth()->user();
@endphp

<header class="sticky top-0 z-30 border-b border-white/50 bg-white/88 backdrop-blur-xl">
    <div class="crm-shell-header px-3 py-3 sm:px-6 lg:px-8 xl:px-10">
        <div class="flex min-w-0 items-start gap-3">
            <button
                type="button"
                class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm lg:hidden"
                @click="sidebarOpen = true"
                aria-label="Open menu"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round" />
                </svg>
            </button>

            <div class="crm-shell-header-copy">
                <p class="crm-section-title">Workspace</p>
                <div class="mt-0.5">
                    @if (isset($header))
                        {{ $header }}
                    @else
                        <h1 class="font-display text-xl font-semibold text-slate-950">Interior CRM</h1>
                    @endif
                </div>
            </div>
        </div>

        <div class="crm-shell-header-actions">
            @if (session('status'))
                <div class="hidden rounded-full bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 lg:block">
                    {{ session('status') }}
                </div>
            @endif

            @if ($user?->isEmployee())
                <div class="hidden sm:block">
                    <livewire:access.venue-switcher />
                </div>
            @else
                <div class="hidden sm:inline-flex">
                    <span class="crm-chip bg-slate-950 text-white">Global Admin Context</span>
                </div>
                <div class="sm:hidden">
                    <span class="crm-chip bg-slate-950 text-white">Admin</span>
                </div>
            @endif

            <div class="hidden rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm md:block">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Signed in as</p>
                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $user?->name }}</p>
            </div>

            <div class="max-w-[8.5rem] truncate rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 md:hidden">
                {{ $user?->name }}
            </div>
        </div>
    </div>

    @if ($user?->isEmployee())
        <div class="border-t border-slate-100 px-3 py-2.5 sm:hidden">
            <livewire:access.venue-switcher />
        </div>
    @endif
</header>
