@include('employee.ledgers.partials.index', [
    'createRoute' => 'employee.daily-income.create',
    'editRoute' => 'employee.daily-income.edit',
    'entryClass' => \App\Models\DailyIncomeEntry::class,
    'indexRoute' => 'employee.daily-income.index',
    'printDateRoute' => 'employee.daily-income.print-date',
    'moduleDescription' => 'Track venue-scoped income rows with secure attachments, date totals, and a clean grand total.',
    'moduleLabel' => 'Daily Income',
    'vendorOptions' => collect(),
])
