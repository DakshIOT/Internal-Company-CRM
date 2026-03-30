<?php

namespace App\Http\Requests;

use App\Support\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::ADMIN) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('services', 'name')],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('services', 'code')],
            'standard_rate' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
