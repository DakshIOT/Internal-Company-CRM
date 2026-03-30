<?php

namespace App\Http\Requests\Admin\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('packages', 'name')],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('packages', 'code')],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'sort_orders' => ['nullable', 'array'],
            'sort_orders.*' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
