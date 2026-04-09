<?php

namespace App\Http\Requests\Admin\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Service;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $service = $this->route('service');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('services', 'name')->ignore($service?->id)],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('services', 'code')->ignore($service?->id)],
            'standard_rate' => ['required', 'numeric', 'min:0'],
            'person_input_mode' => ['nullable', Rule::in([
                Service::PERSON_MODE_FIXED,
                Service::PERSON_MODE_EMPLOYEE,
                Service::PERSON_MODE_NONE,
            ])],
            'default_persons' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'package_ids' => ['nullable', 'array'],
            'package_ids.*' => ['integer', Rule::exists('packages', 'id')],
        ];
    }
}
