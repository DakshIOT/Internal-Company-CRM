<?php

namespace App\Http\Requests\Admin\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $venue = $this->route('venue');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('venues', 'code')->ignore($venue?->id)],
            'is_active' => ['nullable', 'boolean'],
            'vendor_slots' => ['required', 'array', 'size:4'],
            'vendor_slots.*' => ['nullable', 'string', 'max:120'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }
}
