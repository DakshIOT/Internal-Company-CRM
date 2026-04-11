<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Interior CRM') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|sora:600,700&display=swap" rel="stylesheet" />

        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="crm-body font-sans antialiased text-slate-900">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen min-w-0 lg:pl-64 xl:pl-72">
            <div
                x-cloak
                x-show="sidebarOpen"
                class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
                @click="sidebarOpen = false"
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-50 w-[18.25rem] max-w-[86vw] transform overflow-y-auto border-r border-white/10 bg-slate-950/95 text-white shadow-2xl transition duration-300 ease-out lg:w-64 lg:max-w-none lg:translate-x-0 xl:w-72"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                @include('layouts.partials.sidebar')
            </aside>

            <div class="relative min-h-screen min-w-0">
                @include('layouts.partials.topbar', ['header' => $header ?? null])

                <main class="min-w-0 px-3 py-4 sm:px-6 sm:py-5 lg:px-8 xl:px-10">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
