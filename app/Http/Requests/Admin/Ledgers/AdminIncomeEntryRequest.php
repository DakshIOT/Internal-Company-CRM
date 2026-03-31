<?php

namespace App\Http\Requests\Admin\Ledgers;

use App\Http\Requests\Concerns\HasAttachmentRules;
use App\Http\Requests\Concerns\HasLedgerEntryRules;
use Illuminate\Foundation\Http\FormRequest;

class AdminIncomeEntryRequest extends FormRequest
{
    use HasAttachmentRules;
    use HasLedgerEntryRules;

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return array_merge($this->ledgerEntryRules(), $this->attachmentRules());
    }
}
