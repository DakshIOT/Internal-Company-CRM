<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-950">Create service</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Define a reusable service and the default rate employees will inherit later.</p>
        </div>
    </x-slot>

    @include('admin.master-data.services._form')
</x-app-layout>
