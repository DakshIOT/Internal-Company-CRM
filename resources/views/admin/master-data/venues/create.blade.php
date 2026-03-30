<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-950">Create venue</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Add a venue with its initial four vendor slots and activation state.</p>
        </div>
    </x-slot>

    @include('admin.master-data.venues._form')
</x-app-layout>
