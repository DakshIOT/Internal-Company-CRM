<?php

namespace App\Http\Requests\Employee\Ledgers;

use App\Http\Requests\Concerns\HasAttachmentRules;
use App\Http\Requests\Concerns\HasLedgerEntryRules;
use Illuminate\Foundation\Http\FormRequest;

class SimpleLedgerEntryRequest extends FormRequest
{
    use HasAttachmentRules;
    use HasLedgerEntryRules;

    public function authorize(): bool
    {
        return (bool) $this->user()?->isEmployee();
    }

    public function rules(): array
    {
        return array_merge($this->ledgerEntryRules(), $this->attachmentRules());
    }
}
