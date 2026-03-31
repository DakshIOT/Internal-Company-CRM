@php
    use App\Support\Money;
@endphp

<section class="crm-panel overflow-hidden">
    <div class="crm-table-wrap rounded-none border-0">
        <table class="crm-table min-w-[1100px]">
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
                        <td>{{ $entry->attachments_count }}</td>
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
