<?php

namespace App\Http\Requests\Employee\Functions;

use Illuminate\Foundation\Http\FormRequest;

class FunctionPackageLinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isEmployee();
    }

    public function rules(): array
    {
        return [
            'service_lines' => ['required', 'array', 'min:1'],
            'service_lines.*.is_selected' => ['nullable', 'boolean'],
            'service_lines.*.persons' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'service_lines.*.rate' => ['nullable', 'numeric', 'min:0'],
            'service_lines.*.extra_charge' => ['nullable', 'numeric', 'min:0'],
            'service_lines.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
