<?php

namespace App\Http\Requests\Admin\MasterData;

use App\Support\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')->ignore($employee?->id)],
            'role' => ['required', 'string', Rule::in(Role::all())],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
