@php
    use App\Support\Money;

    $currentVenue = $currentVenue ?? null;
    $showVendor = $showVendor ?? false;
    $showVenue = $showVenue ?? false;
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $moduleLabel }} Date Print | {{ $printDate->format('d M Y') }}</title>
        <style>
            :root {
                color-scheme: light;
                --ink: #0f172a;
                --muted: #64748b;
                --line: #dbe4f0;
                --panel: #ffffff;
                --accent: #0f172a;
                --soft: #f8fbff;
            }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                background: #eef5ff;
                color: var(--ink);
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            a { color: #0f5aa6; text-decoration: none; }
            a:hover { text-decoration: underline; }

            .print-toolbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 1.25rem 1.5rem;
                border-bottom: 1px solid rgba(148, 163, 184, 0.2);
                background: linear-gradient(135deg, #081126, #17384f);
                color: white;
            }

            .print-toolbar__meta strong {
                display: block;
                font-size: 1rem;
            }

            .print-toolbar__meta span {
                display: block;
                margin-top: 0.35rem;
                color: rgba(255, 255, 255, 0.75);
                font-size: 0.95rem;
            }

            .print-toolbar__actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            .print-button {
                border: 1px solid rgba(255, 255, 255, 0.25);
                border-radius: 999px;
                padding: 0.7rem 1.1rem;
                background: rgba(255, 255, 255, 0.12);
                color: white;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
            }

            .print-button--primary {
                background: white;
                color: var(--accent);
                border-color: white;
            }

            .print-shell {
                max-width: 1160px;
                margin: 0 auto;
                padding: 1.5rem;
            }

            .print-sheet {
                background: var(--panel);
                border-radius: 1.75rem;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
                padding: 1.5rem;
            }

            .print-header,
            .print-section {
                border: 1px solid var(--line);
                border-radius: 1.5rem;
                background: white;
            }

            .print-header {
                padding: 1.5rem;
            }

            .eyebrow {
                letter-spacing: 0.22em;
                text-transform: uppercase;
                color: var(--muted);
                font-size: 0.72rem;
                font-weight: 700;
            }

            .header-grid {
                display: grid;
                gap: 1rem;
                grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
                align-items: start;
            }

            .header-title {
                margin: 0.4rem 0 0;
                font-size: 2rem;
                line-height: 1.1;
                font-weight: 700;
            }

            .header-copy {
                margin: 0.75rem 0 0;
                color: var(--muted);
                line-height: 1.7;
                max-width: 42rem;
            }

            .chip-row {
                display: flex;
                flex-wrap: wrap;
                gap: 0.55rem;
                margin-top: 1rem;
            }

            .chip {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 0.35rem 0.7rem;
                background: #eef6ff;
                color: #0b5da7;
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.12em;
            }

            .summary-grid {
                display: grid;
                gap: 0.85rem;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .summary-card {
                border: 1px solid var(--line);
                border-radius: 1.1rem;
                padding: 1rem 1.1rem;
                background: var(--soft);
            }

            .summary-card__label {
                color: var(--muted);
                letter-spacing: 0.18em;
                text-transform: uppercase;
                font-size: 0.68rem;
                font-weight: 700;
            }

            .summary-card__value {
                margin-top: 0.55rem;
                font-size: 1.5rem;
                font-weight: 700;
            }

            .print-section {
                margin-top: 1rem;
                padding: 1.25rem;
            }

            .section-title {
                margin: 0 0 0.9rem;
                font-size: 1.05rem;
                font-weight: 700;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                padding: 0.8rem 0.7rem;
                border-top: 1px solid var(--line);
                vertical-align: top;
                text-align: left;
                font-size: 0.92rem;
            }

            th {
                border-top: 0;
                color: var(--muted);
                letter-spacing: 0.14em;
                text-transform: uppercase;
                font-size: 0.68rem;
                font-weight: 700;
                background: #f8fbff;
            }

            .entry-name {
                font-weight: 700;
            }

            .entry-meta {
                color: var(--muted);
                font-size: 0.8rem;
            }

            .attachment-list {
                display: grid;
                gap: 0.65rem;
            }

            .attachment-item {
                border: 1px solid var(--line);
                border-radius: 0.9rem;
                padding: 0.7rem 0.8rem;
                background: var(--soft);
            }

            .attachment-name {
                font-weight: 700;
            }

            .attachment-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.7rem;
                margin-top: 0.35rem;
                font-size: 0.83rem;
            }

            .muted {
                color: var(--muted);
            }

            @page {
                size: A4 portrait;
                margin: 12mm;
            }

            @media print {
                body {
                    background: white;
                }

                .print-toolbar {
                    display: none;
                }

                .print-shell {
                    max-width: none;
                    margin: 0;
                    padding: 0;
                }

                .print-sheet {
                    padding: 0;
                    border-radius: 0;
                    box-shadow: none;
                }

                .print-header,
                .print-section {
                    break-inside: avoid;
                    box-shadow: none;
                }

                a {
                    color: inherit;
                    text-decoration: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="print-toolbar">
            <div class="print-toolbar__meta">
                <strong>{{ $moduleLabel }} date print</strong>
                <span>{{ $printDate->format('d M Y') }}{{ $currentVenue ? ' | '.$currentVenue->name : '' }}</span>
            </div>
            <div class="print-toolbar__actions">
                <a href="{{ $backRoute }}" class="print-button">Back to list</a>
                <button type="button" onclick="window.print()" class="print-button print-button--primary">Print this date</button>
            </div>
        </div>

        <div class="print-shell">
            <div class="print-sheet">
                <section class="print-header">
                    <div class="header-grid">
                        <div>
                            <div class="eyebrow">{{ $moduleLabel }}</div>
                            <h1 class="header-title">{{ $printDate->format('d M Y') }}</h1>
                            <p class="header-copy">
                                Date-wise print view with every entry recorded on this date, including attachments and secure download links.
                            </p>
                            <div class="chip-row">
                                <span class="chip">{{ $totals['entry_count'] }} entries</span>
                                @if ($currentVenue)
                                    <span class="chip">{{ $currentVenue->name }}</span>
                                @endif
                                @if ($showVendor)
                                    <span class="chip">Vendor wise</span>
                                @endif
                            </div>
                        </div>

                        <div class="summary-grid">
                            <div class="summary-card">
                                <div class="summary-card__label">Print date</div>
                                <div class="summary-card__value">{{ $printDate->format('d M Y') }}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-card__label">Date total</div>
                                <div class="summary-card__value">{{ Money::formatMinor($totals['amount_minor']) }}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-card__label">Rows in print</div>
                                <div class="summary-card__value">{{ $totals['entry_count'] }}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-card__label">Context</div>
                                <div class="summary-card__value" style="font-size: 1rem;">{{ $currentVenue?->name ?? 'Global admin ledger' }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="print-section">
                    <h2 class="section-title">Entries on {{ $printDate->format('d M Y') }}</h2>
                    <table>
                        <thead>
                            <tr>
                                @if ($showVenue)
                                    <th>Venue</th>
                                @endif
                                @if ($showVendor)
                                    <th>Vendor</th>
                                @endif
                                <th>Name</th>
                                <th>Notes</th>
                                <th>Files</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr>
                                    @if ($showVenue)
                                        <td>{{ $entry->venue->name ?? '-' }}</td>
                                    @endif
                                    @if ($showVendor)
                                        <td>{{ $entry->vendor_name_snapshot ?: ($entry->venueVendor->name ?? 'No vendor') }}</td>
                                    @endif
                                    <td>
                                        <div class="entry-name">{{ $entry->name }}</div>
                                        @if (! $currentVenue && $entry->user)
                                            <div class="entry-meta">{{ $entry->user->name }} | {{ $entry->user->roleLabel() }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $entry->notes ?: 'No notes' }}</td>
                                    <td>{{ $entry->attachments_count }}</td>
                                    <td>{{ Money::formatMinor($entry->amount_minor) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>

                <section class="print-section">
                    <h2 class="section-title">Attachments and download links</h2>
                    <div class="attachment-list">
                        @foreach ($entries as $entry)
                            <div class="attachment-item">
                                <div class="entry-name">{{ $entry->name }}</div>
                                <div class="entry-meta">{{ optional($entry->entry_date)->format('d M Y') }} | {{ Money::formatMinor($entry->amount_minor) }}</div>
                                @if ($entry->attachments->isEmpty())
                                    <p class="muted" style="margin: 0.65rem 0 0;">No attachments recorded for this entry.</p>
                                @else
                                    <div class="attachment-list" style="margin-top: 0.75rem;">
                                        @foreach ($entry->attachments as $attachment)
                                            <div class="attachment-item">
                                                <div class="attachment-name">{{ $attachment->original_name }}</div>
                                                <div class="attachment-actions">
                                                    @if ($attachment->canPreviewInline())
                                                        <a href="{{ route($previewRoute, [$routeKey => $entry, 'attachment' => $attachment]) }}" target="_blank" rel="noopener">Open</a>
                                                    @endif
                                                    <a href="{{ route($downloadRoute, [$routeKey => $entry, 'attachment' => $attachment]) }}">Download</a>
                                                    <span class="muted">{{ route($downloadRoute, [$routeKey => $entry, 'attachment' => $attachment]) }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>

        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    </body>
</html>
