<?php

namespace App\Http\Requests\Concerns;

trait HasAttachmentRules
{
    protected function allowedAttachmentExtensions(): array
    {
        return [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'bmp',
            'svg',
            'avif',
            'heic',
            'heif',
            'tif',
            'tiff',
            'pdf',
            'doc',
            'docx',
            'odt',
            'xls',
            'xlsx',
            'ods',
            'csv',
            'odf',
        ];
    }

    protected function attachmentRules(bool $required = false): array
    {
        return [
            'attachments' => [$required ? 'required' : 'nullable', 'array'],
            'attachments.*' => [
                'file',
                'max:25600',
                'mimes:'.implode(',', $this->allowedAttachmentExtensions()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.*.mimes' => 'Only image files, PDF, CSV, Excel, Word, and ODF documents can be attached. Files like .css or .txt are not allowed.',
            'attachments.*.max' => 'Each attachment must be 25 MB or smaller.',
            'attachments.*.file' => 'Each selected attachment must be a valid file.',
        ];
    }
}
