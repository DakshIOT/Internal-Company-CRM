<?php

namespace App\Http\Requests\Employee\Functions;

use App\Http\Requests\Concerns\HasAttachmentRules;
use App\Support\TransactionMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FunctionDiscountRequest extends FormRequest
{
    use HasAttachmentRules;

    public function authorize(): bool
    {
        return (bool) $this->user()?->isEmployee();
    }

    public function rules(): array
    {
        return array_merge([
            'entry_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:120'],
            'mode' => ['required', 'string', Rule::in(TransactionMode::all())],
            'amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ], $this->attachmentRules());
    }
}
