@php
    use App\Support\Money;
@endphp

<section class="crm-panel overflow-hidden">
    <div class="crm-table-wrap rounded-none border-0">
        <table class="crm-table min-w-[920px] xl:min-w-[1020px] 2xl:min-w-[1100px]">
            <thead>
                <tr>
                    <th>Entry Date</th>
                    @if ($supportsVenue)
                        <th>Venue</th>
                    @endif
                    <th>User</th>
                    <th>Employee Type</th>
                    @if ($showVendor)
                        <th>Vendor</th>
                    @endif
                    <th>Name</th>
                    <th>Amount</th>
                    <th>Notes</th>
                    <th>Files</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($entries as $entry)
                    @php
                        $firstAttachment = $entry->attachments->first();
                    @endphp
                    <tr>
                        <td>{{ optional($entry->entry_date)->format('d M Y') }}</td>
                        @if ($supportsVenue)
                            <td>{{ $entry->venue->name ?? '-' }}</td>
                        @endif
                        <td>{{ $entry->user->name ?? '-' }}</td>
                        <td>{{ $entry->user?->roleLabel() ?? '-' }}</td>
                        @if ($showVendor)
                            <td>{{ $entry->vendor_name_snapshot ?: 'No vendor' }}</td>
                        @endif
                        <td>{{ $entry->name }}</td>
                        <td>{{ Money::formatMinor($entry->amount_minor) }}</td>
                        <td>{{ $entry->notes ?: 'No notes' }}</td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full bg-slate-100 px-2 py-1 font-semibold text-slate-700">
                                    {{ $entry->attachments_count }}
                                </span>
                                @if ($firstAttachment)
                                    <a href="{{ route('admin.reports.attachments.preview', $firstAttachment) }}" class="text-cyan-700 hover:text-cyan-800 hover:underline" target="_blank" rel="noopener">
                                        Open
                                    </a>
                                    <a href="{{ route('admin.reports.attachments.download', $firstAttachment) }}" class="text-slate-700 hover:text-slate-900 hover:underline">
                                        Download
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 6 + ($supportsVenue ? 1 : 0) + ($showVendor ? 1 : 0) }}" class="px-4 py-8 text-center text-sm text-slate-500">
                            No report rows match the current filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
