<?php

namespace App\Http\Requests;

use App\Support\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::ADMIN) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'service_ids' => array_values(array_filter((array) $this->input('service_ids', []))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('packages', 'name')],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('packages', 'code')],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', Rule::exists('services', 'id')],
            'service_order' => ['nullable', 'array'],
            'service_order.*' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];
    }
}
