<?php

namespace App\Http\Requests\Employee\Functions;

use Illuminate\Foundation\Http\FormRequest;

class FunctionPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isEmployee();
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:packages,id'],
        ];
    }
}
