<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="crm-section-title">Admin Master Data</p>
            <h1 class="mt-1 font-display text-3xl font-semibold text-slate-950">Function print settings</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Manage the shared terms and conditions printed on day-wise Function Entry printouts.
            </p>
        </div>
    </x-slot>

    @include('admin.master-data.partials.nav')

    <section class="crm-panel p-6">
        <form method="POST" action="{{ route('admin.master-data.function-print-settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="crm-field">
                    <label for="function_terms_and_conditions" class="crm-field-label">Function print terms and conditions</label>
                    <textarea
                        id="function_terms_and_conditions"
                        name="function_terms_and_conditions"
                        rows="16"
                        class="crm-input min-h-[24rem] w-full"
                        placeholder="Enter the terms that should appear in Function day-print sheets."
                    >{{ old('function_terms_and_conditions', $settings->function_terms_and_conditions) }}</textarea>
                    <x-input-error :messages="$errors->get('function_terms_and_conditions')" class="mt-2" />
                </div>

                <div class="space-y-4">
                    <article class="rounded-[1.5rem] border border-slate-100 bg-slate-50 p-5">
                        <p class="crm-section-title">Print output</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-950">What the employee print will show</h2>
                        <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                            <li>Every Function Entry for the selected date in the selected venue.</li>
                            <li>Packages and service lines.</li>
                            <li>Extra charges, installments, discounts, and attachment links.</li>
                            <li>Terms and conditions from this screen.</li>
                            <li>Customer signature and manager signature boxes with dates.</li>
                        </ul>
                    </article>

                    <article class="rounded-[1.5rem] border border-cyan-100 bg-cyan-50/60 p-5">
                        <p class="crm-section-title">Fallback</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            If this field is cleared, the system falls back to the built-in default terms so print output never renders empty.
                        </p>
                    </article>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="crm-button crm-button-primary justify-center px-5 py-3">
                    Save print settings
                </button>
            </div>
        </form>
    </section>
</x-app-layout>

