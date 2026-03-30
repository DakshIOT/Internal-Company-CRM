<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-950">Create employee</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Create an internal CRM account before mapping venue and module access.</p>
        </div>
    </x-slot>

    @include('admin.master-data.employees._form')
</x-app-layout>
