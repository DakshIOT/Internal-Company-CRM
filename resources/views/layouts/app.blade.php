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
        <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:pl-72">
            <div
                x-cloak
                x-show="sidebarOpen"
                class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
                @click="sidebarOpen = false"
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-50 w-72 transform border-r border-white/10 bg-slate-950/95 text-white shadow-2xl transition duration-300 ease-out lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                @include('layouts.partials.sidebar')
            </aside>

            <div class="relative min-h-screen">
                @include('layouts.partials.topbar', ['header' => $header ?? null])

                <main class="px-4 pb-8 pt-24 sm:px-6 lg:px-8 xl:px-10">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
