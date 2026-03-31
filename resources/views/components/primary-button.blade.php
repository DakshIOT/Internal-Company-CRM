<button {{ $attributes->merge(['type' => 'submit', 'data-loading-label' => 'Working...', 'class' => 'crm-button crm-button-primary justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
