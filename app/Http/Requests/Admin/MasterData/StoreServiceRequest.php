<?php

namespace App\Http\Requests\Admin\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('services', 'name')],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('services', 'code')],
            'standard_rate' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
