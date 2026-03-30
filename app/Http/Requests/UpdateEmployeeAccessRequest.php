<?php

namespace App\Http\Requests;

use App\Support\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'venues' => ['nullable', 'array'],
            'venues.*.assigned' => ['nullable', 'boolean'],
            'venues.*.frozen_fund' => ['nullable', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'venues.*.services' => ['nullable', 'array'],
            'venues.*.services.*' => ['integer', Rule::exists('services', 'id')],
            'venues.*.packages' => ['nullable', 'array'],
            'venues.*.packages.*' => ['integer', Rule::exists('packages', 'id')],
        ];
    }
}
