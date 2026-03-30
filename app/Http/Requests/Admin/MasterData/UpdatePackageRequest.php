<?php

namespace App\Http\Requests\Admin\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $package = $this->route('package');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('packages', 'name')->ignore($package?->id)],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('packages', 'code')->ignore($package?->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'sort_orders' => ['nullable', 'array'],
            'sort_orders.*' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
