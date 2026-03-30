<?php

namespace App\Http\Requests\Concerns;

trait HasAttachmentRules
{
    protected function attachmentRules(bool $required = false): array
    {
        return [
            'attachments' => [$required ? 'required' : 'nullable', 'array'],
            'attachments.*' => [
                'file',
                'max:25600',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv',
            ],
        ];
    }
}
