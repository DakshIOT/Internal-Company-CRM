<?php

namespace App\Http\Requests\Admin\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFunctionPrintSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'function_terms_and_conditions' => ['nullable', 'string', 'max:20000'],
        ];
    }
}

