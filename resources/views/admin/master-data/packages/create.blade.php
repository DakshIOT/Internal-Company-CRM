<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold text-slate-950">Create package</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Create a package and define which services it contains.</p>
        </div>
    </x-slot>

    @include('admin.master-data.packages._form')
</x-app-layout>
