<button {{ $attributes->merge(['type' => 'submit', 'class' => 'crm-button crm-button-primary justify-center focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
