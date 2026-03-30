<?php

namespace App\Http\Requests\Employee\Functions;

use App\Http\Requests\Concerns\HasAttachmentRules;
use Illuminate\Foundation\Http\FormRequest;

class FunctionEntryRequest extends FormRequest
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
            'notes' => ['nullable', 'string'],
        ], $this->attachmentRules());
    }
}
