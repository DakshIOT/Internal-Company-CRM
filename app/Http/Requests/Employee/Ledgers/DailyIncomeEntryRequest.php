<?php

namespace App\Http\Requests\Employee\Ledgers;

use App\Http\Requests\Concerns\HasAttachmentRules;
use App\Http\Requests\Concerns\HasLedgerEntryRules;
use App\Support\Role;
use Illuminate\Foundation\Http\FormRequest;

class DailyIncomeEntryRequest extends FormRequest
{
    use HasAttachmentRules;
    use HasLedgerEntryRules;

    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole([Role::EMPLOYEE_A, Role::EMPLOYEE_B]);
    }

    public function rules(): array
    {
        return array_merge($this->ledgerEntryRules(), $this->attachmentRules());
    }
}
