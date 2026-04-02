@props([
    'id',
    'name',
    'label',
    'value' => null,
    'messages' => [],
    'autocomplete' => null,
    'required' => false,
    'placeholder' => null,
    'inputClass' => 'crm-input w-full',
])

<div x-data="{ showPassword: false }">
    <x-input-label :for="$id" :value="$label" />

    <div class="relative mt-2">
        <x-text-input
            :id="$id"
            :name="$name"
            x-bind:type="showPassword ? 'text' : 'password'"
            :value="$value"
            :autocomplete="$autocomplete"
            :required="$required"
            :placeholder="$placeholder"
            class="{{ trim($inputClass.' pr-20') }}"
        />

        <button
            type="button"
            class="crm-password-toggle"
            @click="showPassword = ! showPassword"
            x-text="showPassword ? 'Hide' : 'Show'"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            :aria-pressed="showPassword.toString()"
        ></button>
    </div>

    <x-input-error :messages="$messages" class="mt-2" />
</div>
