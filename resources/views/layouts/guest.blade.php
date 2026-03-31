<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Interior CRM') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|sora:600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="crm-body font-sans antialiased text-slate-900">
        <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-950 px-4 py-8 sm:px-6">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.18),_transparent_28%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.16),_transparent_32%),linear-gradient(145deg,_#020617,_#111827_56%,_#0f172a)]"></div>
            <div class="absolute inset-0 bg-[linear-gradient(120deg,transparent_0%,rgba(255,255,255,0.03)_48%,transparent_100%)]"></div>

            <div class="relative w-full max-w-lg">
                <section class="crm-auth-card">
                    <div class="mb-7">
                        <div class="mb-5 inline-flex rounded-full border border-cyan-100 bg-cyan-50 px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-cyan-700">
                            Internal Company CRM
                        </div>

                        <div class="max-w-lg">
                            <h1 class="font-display text-[2rem] font-semibold leading-tight text-slate-950 sm:text-[2.25rem]">Secure workspace login</h1>
                            <p class="mt-3 max-w-md text-[0.97rem] leading-7 text-slate-600">Use your assigned work email to enter the internal CRM. Employees choose their venue immediately after sign in.</p>
                        </div>
                    </div>

                    {{ $slot }}
                </section>
            </div>
        </div>
    </body>
</html>
