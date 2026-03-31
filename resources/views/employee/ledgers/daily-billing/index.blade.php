@include('employee.ledgers.partials.index', [
    'createRoute' => 'employee.daily-billing.create',
    'editRoute' => 'employee.daily-billing.edit',
    'entryClass' => \App\Models\DailyBillingEntry::class,
    'indexRoute' => 'employee.daily-billing.index',
    'moduleDescription' => 'Track venue-scoped billing rows with secure attachments, date totals, and a clean grand total.',
    'moduleLabel' => 'Daily Billing',
    'vendorOptions' => collect(),
])
