@php
    $attachments = $entry->attachments ?? collect();
    $showUpload = $showUpload ?? true;
    $allowDelete = $allowDelete ?? false;
@endphp

<div class="space-y-3">
    @if ($showUpload)
        <div class="crm-upload">
            <label class="crm-field-label" for="{{ $inputId ?? 'attachments' }}">Attachments</label>
            <input
                id="{{ $inputId ?? 'attachments' }}"
                name="attachments[]"
                type="file"
                multiple
                accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.avif,.heic,.heif,.tif,.tiff,.pdf,.doc,.docx,.odt,.xls,.xlsx,.ods,.csv,.odf"
                class="mt-3 block w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
            />
            <p class="mt-2 text-xs text-slate-500">Images, PDF, Word, Excel, CSV, and ODF files up to 25 MB per file.</p>
            <p class="mt-2 rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">
                Unsupported files such as <span class="font-semibold">.css</span> or <span class="font-semibold">.txt</span> will be rejected.
            </p>
            <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
            <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
        </div>
    @endif

    @if ($attachments->isNotEmpty())
        <div class="space-y-2">
            @foreach ($attachments as $attachment)
                <div class="flex flex-col gap-3 rounded-[1.25rem] border border-slate-100 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $attachment->original_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ number_format($attachment->size_bytes / 1024, 1) }} KB
                            @if ($attachment->mime_type)
                                | {{ $attachment->mime_type }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($attachment->canPreviewInline())
                            <a
                                href="{{ route($previewRoute, [$routeKey => $entry, 'attachment' => $attachment]) }}"
                                target="_blank"
                                class="crm-button crm-button-secondary px-4 py-2"
                            >
                                View
                            </a>
                        @endif
                        <a
                            href="{{ route($downloadRoute, [$routeKey => $entry, 'attachment' => $attachment]) }}"
                            class="crm-button crm-button-secondary px-4 py-2"
                        >
                            Download
                        </a>
                        @if ($allowDelete)
                            <button type="submit" form="attachment-delete-{{ $routeKey }}-{{ $attachment->id }}" class="crm-button border border-rose-200 bg-rose-50 px-4 py-2 text-rose-600 hover:border-rose-300">
                                Remove
                            </button>
                            <form id="attachment-delete-{{ $routeKey }}-{{ $attachment->id }}" method="POST" action="{{ route($destroyRoute, [$routeKey => $entry, 'attachment' => $attachment]) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @elseif (! empty($emptyMessage))
        <p class="text-sm text-slate-500">{{ $emptyMessage }}</p>
    @endif
</div>
