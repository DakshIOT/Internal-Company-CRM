<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-950">Edit employee</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Update the account profile, role, activation, and credentials for this user.</p>
        </div>
    </x-slot>

    @include('admin.master-data.employees._form')
</x-app-layout>
