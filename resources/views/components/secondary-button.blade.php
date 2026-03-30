<button {{ $attributes->merge(['type' => 'button', 'class' => 'crm-button crm-button-secondary justify-center']) }}>
    {{ $slot }}
</button>
