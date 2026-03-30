<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-950">Account security</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Identity details stay admin-managed in this internal CRM. Personal account access is limited to password maintenance.
            </p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <article class="crm-panel p-6">
                <p class="crm-section-title">Account identity</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Name</p>
                        <p class="mt-3 text-lg font-semibold text-slate-950">{{ $user->name }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Email</p>
                        <p class="mt-3 text-lg font-semibold text-slate-950">{{ $user->email }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Role</p>
                        <p class="mt-3 text-lg font-semibold text-slate-950">{{ $user->roleLabel() }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</p>
                        <p class="mt-3 text-lg font-semibold text-slate-950">{{ $user->is_active ? 'Active' : 'Inactive' }}</p>
                    </div>
                </div>
                <p class="mt-5 text-sm leading-6 text-slate-600">
                    Identity changes, role changes, venue assignment, and access updates are handled by admin through the Phase 2 master-data controls.
                </p>
            </article>

            <article class="crm-panel p-6">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </article>
        </section>
    </div>
</x-app-layout>
