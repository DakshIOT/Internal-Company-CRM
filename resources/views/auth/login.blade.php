<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <div>
            <x-input-label for="email" value="Work Email" />
            <x-text-input id="email" class="crm-input mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-password-field
                id="password"
                name="password"
                label="Password"
                :messages="$errors->get('password')"
                autocomplete="current-password"
                :required="true"
                input-class="crm-input block w-full"
            />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center text-sm font-medium text-slate-600">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-cyan-600 shadow-sm focus:ring-cyan-500" name="remember">
                <span class="ml-2">Keep this session active</span>
            </label>
        </div>

        <div class="rounded-[1.5rem] border border-slate-100 bg-slate-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">After Login</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">Employees continue to venue selection. Admin goes straight to the global workspace.</p>
        </div>

        <div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-slate-600 underline-offset-4 transition hover:text-slate-950 hover:underline focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2" href="{{ route('password.request') }}">
                    Forgot your password?
                </a>
            @endif

            <x-primary-button class="crm-button min-w-[10rem] justify-center" data-loading-label="Signing in...">
                Login
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
