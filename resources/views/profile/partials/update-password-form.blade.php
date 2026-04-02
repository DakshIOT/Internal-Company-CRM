<section>
    <header>
        <h2 class="font-display text-2xl font-semibold text-slate-950">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div class="crm-field">
            <x-password-field
                id="current_password"
                name="current_password"
                :label="__('Current Password')"
                :messages="$errors->updatePassword->get('current_password')"
                autocomplete="current-password"
                input-class="block w-full"
            />
        </div>

        <div class="crm-field">
            <x-password-field
                id="password"
                name="password"
                :label="__('New Password')"
                :messages="$errors->updatePassword->get('password')"
                autocomplete="new-password"
                input-class="block w-full"
            />
        </div>

        <div class="crm-field">
            <x-password-field
                id="password_confirmation"
                name="password_confirmation"
                :label="__('Confirm Password')"
                :messages="$errors->updatePassword->get('password_confirmation')"
                autocomplete="new-password"
                input-class="block w-full"
            />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
