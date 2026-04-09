<?php

namespace App\Http\Requests\Admin\Reports;

use App\Reports\Filters\ReportFilters;
use App\Support\Reports\ReportModule;
use App\Support\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', Role::employeeRoles());
                }),
            ],
            'employee_role' => ['nullable', Rule::in(Role::all())],
            'module' => ['nullable', Rule::in(ReportModule::all())],
            'vendor_id' => ['nullable', 'integer', 'exists:venue_vendors,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'search' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function filters(): ReportFilters
    {
        return ReportFilters::fromArray($this->validated());
    }
}
