<?php

namespace App\Http\Requests\Concerns;

trait HasLedgerEntryRules
{
    protected function ledgerEntryRules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
