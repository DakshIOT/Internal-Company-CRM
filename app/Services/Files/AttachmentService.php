<?php

namespace App\Services\Files;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    public function storeFor(Model $attachable, array $files, User $user): void
    {
        $disk = config('filesystems.default', 'local');
        $directory = 'attachments/'.$this->directoryFor($attachable).'/'.$attachable->getKey();

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $extension = $file->getClientOriginalExtension();
            $filename = (string) Str::uuid().($extension ? '.'.$extension : '');
            $path = $file->storeAs($directory, $filename, ['disk' => $disk]);

            $attachable->attachments()->create([
                'uploaded_by' => $user->getKey(),
                'disk' => $disk,
                'storage_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    public function delete(Attachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->storage_path);
        $attachment->delete();
    }

    protected function directoryFor(Model $attachable): string
    {
        return Str::kebab(class_basename($attachable));
    }
}
