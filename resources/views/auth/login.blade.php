<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="space-y-2">
        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Phase 1 Foundation</p>
        <h3 class="text-3xl font-semibold tracking-tight text-slate-950">Sign in to continue</h3>
        <p class="text-sm leading-6 text-slate-600">
            Access is limited to internal company users. Employees will select a venue immediately after login.
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mt-8">
            <x-input-label for="email" value="Work Email" />
            <x-text-input id="email" class="crm-input mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Password" />

            <x-text-input id="password" class="crm-input mt-2 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-5 flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center text-sm font-medium text-slate-600">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-cyan-600 shadow-sm focus:ring-cyan-500" name="remember">
                <span class="ml-2">Keep this session active</span>
            </label>

            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                Internal only
            </span>
        </div>

        <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-slate-600 underline-offset-4 transition hover:text-slate-950 hover:underline focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2" href="{{ route('password.request') }}">
                    Forgot your password?
                </a>
            @endif

            <x-primary-button class="crm-button min-w-[10rem] justify-center">
                Log in
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
