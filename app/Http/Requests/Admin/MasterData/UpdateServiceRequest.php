<?php

namespace App\Http\Requests\Admin\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
