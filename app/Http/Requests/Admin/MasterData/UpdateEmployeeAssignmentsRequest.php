<?php

namespace App\Http\Requests\Admin\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'venue_ids' => ['nullable', 'array'],
            'venue_ids.*' => ['integer', 'exists:venues,id'],
            'frozen_funds' => ['nullable', 'array'],
            'frozen_funds.*' => ['nullable', 'numeric', 'min:0'],
            'service_ids_by_venue' => ['nullable', 'array'],
            'service_ids_by_venue.*' => ['nullable', 'array'],
            'service_ids_by_venue.*.*' => ['integer', 'exists:services,id'],
            'package_ids_by_venue' => ['nullable', 'array'],
            'package_ids_by_venue.*' => ['nullable', 'array'],
            'package_ids_by_venue.*.*' => ['integer', 'exists:packages,id'],
        ];
    }
}
