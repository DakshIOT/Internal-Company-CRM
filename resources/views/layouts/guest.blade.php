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
        <div class="relative min-h-screen overflow-hidden bg-slate-950">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,0.22),_transparent_30%),radial-gradient(circle_at_80%_20%,_rgba(59,130,246,0.18),_transparent_32%),linear-gradient(135deg,_#020617,_#0f172a_58%,_#111827)]"></div>
            <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.08),_transparent_52%)] lg:block"></div>

            <div class="relative mx-auto flex min-h-screen max-w-7xl items-center px-4 py-10 sm:px-6 lg:px-8">
                <div class="grid w-full gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:gap-12">
                    <section class="hidden rounded-[2rem] border border-white/10 bg-white/5 p-8 text-white shadow-2xl backdrop-blur-xl lg:flex lg:flex-col lg:justify-between">
                        <div class="space-y-6">
                            <div class="inline-flex items-center gap-3 rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold tracking-[0.18em] text-cyan-100">
                                Internal Company CRM
                            </div>

                            <div class="space-y-4">
                                <h1 class="font-display text-4xl font-semibold leading-tight text-white xl:text-5xl">
                                    Premium venue-based operations for the team that runs the business.
                                </h1>
                                <p class="max-w-xl text-base leading-7 text-slate-300">
                                    Phase 1 establishes the secure foundation: login, fixed roles, mandatory venue selection,
                                    and a mobile-ready shell prepared for the full CRM build.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="crm-glass-panel">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Workflow</p>
                                <p class="mt-3 text-lg font-semibold text-white">Login -> Venue Selection -> Role Dashboard</p>
                            </div>
                            <div class="crm-glass-panel">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Deployment</p>
                                <p class="mt-3 text-lg font-semibold text-white">Hostinger-safe Laravel foundation</p>
                            </div>
                        </div>
                    </section>

                    <section class="crm-auth-card w-full max-w-xl justify-self-center">
                        <div class="mb-8 flex items-center gap-4">
                            <a href="/" class="inline-flex items-center">
                                <x-application-logo class="h-14 w-14" />
                            </a>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-600">Interior CRM</p>
                                <h2 class="mt-1 font-display text-2xl font-semibold text-slate-950">Secure workspace access</h2>
                            </div>
                        </div>

                        {{ $slot }}
                    </section>
                </div>
            </div>
        </div>
    </body>
</html>
