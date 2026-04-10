@php
    use App\Support\Money;

    $attachmentPreviewRoute = 'employee.functions.attachments.preview';
    $attachmentDownloadRoute = 'employee.functions.attachments.download';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Function Date Print | {{ $printDate->format('d M Y') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|sora:600,700&display=swap" rel="stylesheet" />
        <style>
            :root {
                --ink: #0f172a;
                --muted: #64748b;
                --line: #cbd5e1;
                --panel: #ffffff;
                --soft: #f8fafc;
                --accent: #e2e8f0;
            }

            * {
                box-sizing: border-box;
            }

            html,
            body {
                margin: 0;
                padding: 0;
                color: var(--ink);
                font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;
                background: #e2e8f0;
            }

            body {
                padding: 24px;
            }

            .print-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                max-width: 1100px;
                margin: 0 auto 16px;
            }

            .print-toolbar__actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .print-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                border: 1px solid #cbd5e1;
                background: #fff;
                color: var(--ink);
                padding: 10px 18px;
                font-size: 13px;
                font-weight: 700;
                text-decoration: none;
            }

            .print-button--primary {
                border-color: #0f172a;
                background: #0f172a;
                color: #fff;
            }

            .print-sheet {
                max-width: 1100px;
                margin: 0 auto;
                border-radius: 28px;
                background: var(--panel);
                border: 1px solid #e2e8f0;
                box-shadow: 0 28px 80px -40px rgba(15, 23, 42, 0.35);
                overflow: hidden;
            }

            .print-header,
            .print-section {
                padding: 24px 28px;
            }

            .print-header {
                border-bottom: 1px solid #e2e8f0;
            }

            .eyebrow {
                margin: 0;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.22em;
                text-transform: uppercase;
                color: var(--muted);
            }

            .page-title {
                margin: 10px 0 0;
                font-family: 'Sora', ui-sans-serif, system-ui, sans-serif;
                font-size: 30px;
                line-height: 1.1;
                font-weight: 700;
            }

            .page-description {
                margin: 12px 0 0;
                max-width: 760px;
                color: var(--muted);
                font-size: 14px;
                line-height: 1.7;
            }

            .summary-grid {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 12px;
                margin-top: 20px;
            }

            .summary-card {
                border: 1px solid #dbe3ee;
                border-radius: 20px;
                background: var(--soft);
                padding: 16px 18px;
            }

            .summary-card__label {
                margin: 0;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.2em;
                text-transform: uppercase;
                color: var(--muted);
            }

            .summary-card__value {
                margin: 10px 0 0;
                font-size: 24px;
                font-weight: 800;
            }

            .entry-block {
                border-top: 1px solid #e2e8f0;
            }

            .entry-top {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 320px;
                gap: 18px;
                align-items: start;
            }

            .entry-title {
                margin: 10px 0 0;
                font-size: 28px;
                font-weight: 800;
            }

            .chip-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 12px;
            }

            .chip {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 6px 10px;
                background: #f1f5f9;
                color: #475569;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.14em;
                text-transform: uppercase;
            }

            .entry-notes {
                margin: 14px 0 0;
                color: var(--muted);
                font-size: 14px;
                line-height: 1.7;
            }

            .entry-metrics {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .entry-metric {
                border: 1px solid #dbe3ee;
                border-radius: 18px;
                padding: 14px 16px;
                background: #fff;
            }

            .entry-metric--accent {
                background: #0f172a;
                border-color: #0f172a;
                color: #fff;
            }

            .entry-metric__label {
                margin: 0;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.2em;
                text-transform: uppercase;
                color: inherit;
                opacity: 0.72;
            }

            .entry-metric__value {
                margin: 8px 0 0;
                font-size: 22px;
                font-weight: 800;
            }

            .table-section {
                margin-top: 18px;
                border: 1px solid #dbe3ee;
                border-radius: 22px;
                overflow: hidden;
                background: #fff;
            }

            .table-section__title {
                padding: 16px 18px;
                border-bottom: 1px solid #e2e8f0;
                background: #f8fafc;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.22em;
                text-transform: uppercase;
                color: var(--muted);
            }

            table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }

            th,
            td {
                padding: 12px 14px;
                border-bottom: 1px solid #e2e8f0;
                text-align: left;
                vertical-align: top;
                font-size: 12px;
                line-height: 1.6;
                word-break: break-word;
            }

            th {
                background: #f8fafc;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                color: var(--muted);
            }

            tbody tr:last-child td {
                border-bottom: 0;
            }

            .cell-title {
                font-weight: 700;
                color: var(--ink);
            }

            .cell-muted {
                margin-top: 4px;
                color: var(--muted);
                font-size: 11px;
            }

            .link-list {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .link-list a {
                color: var(--ink);
                text-decoration: underline;
            }

            .terms-box {
                border: 1px solid #dbe3ee;
                border-radius: 22px;
                background: var(--soft);
                padding: 18px 20px;
                color: #334155;
                font-size: 13px;
                line-height: 1.8;
                white-space: normal;
            }

            .signature-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
                margin-top: 16px;
            }

            .signature-box {
                border: 1px solid #dbe3ee;
                border-radius: 22px;
                padding: 18px 20px 16px;
            }

            .signature-line {
                margin-top: 56px;
                border-bottom: 1px solid #94a3b8;
            }

            .signature-meta {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                margin-top: 10px;
                color: var(--muted);
                font-size: 12px;
            }

            .print-footer-space {
                height: 6px;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            @media print {
                html,
                body {
                    background: #fff;
                }

                body {
                    padding: 0;
                }

                .print-toolbar {
                    display: none !important;
                }

                .print-sheet {
                    max-width: none;
                    border: 0;
                    border-radius: 0;
                    box-shadow: none;
                    overflow: visible;
                }

                .print-header,
                .print-section {
                    padding: 12px 0;
                }

                .entry-block,
                .table-section,
                .signature-box,
                .summary-card,
                .terms-box {
                    break-inside: auto;
                    page-break-inside: auto;
                }

                .entry-block {
                    page-break-before: auto;
                }

                table,
                thead,
                tbody,
                tr,
                td,
                th {
                    overflow: visible !important;
                }

                a {
                    color: var(--ink) !important;
                    text-decoration: underline;
                }
            }

            @media (max-width: 900px) {
                body {
                    padding: 12px;
                }

                .summary-grid,
                .entry-top,
                .signature-grid {
                    grid-template-columns: 1fr;
                }

                .print-header,
                .print-section {
                    padding: 18px;
                }

                .page-title,
                .entry-title {
                    font-size: 24px;
                }
            }
        </style>
    </head>
    <body>
        <div class="print-toolbar">
            <div>
                <p class="eyebrow">Function Entry</p>
                <strong>Date print preview</strong>
            </div>
            <div class="print-toolbar__actions">
                <a href="{{ route('employee.functions.index', ['entry_date' => $printDate->toDateString()]) }}" class="print-button">Back to list</a>
                <button type="button" onclick="window.print()" class="print-button print-button--primary">Print this date</button>
            </div>
        </div>

        <div class="print-sheet">
            <section class="print-header">
                <p class="eyebrow">Function Entry</p>
                <h1 class="page-title">Date Print Sheet</h1>
                <p class="page-description">
                    Full print view for {{ $printDate->format('d M Y') }} in {{ $currentVenue->name }}, including packages, service rows, extra charges, installments, discounts, attachments, terms, and signatures.
                </p>

                <div class="summary-grid">
                    <article class="summary-card">
                        <p class="summary-card__label">Print date</p>
                        <p class="summary-card__value">{{ $printDate->format('d M Y') }}</p>
                    </article>
                    <article class="summary-card">
                        <p class="summary-card__label">Entries</p>
                        <p class="summary-card__value">{{ $dayTotals['entry_count'] }}</p>
                    </article>
                    <article class="summary-card">
                        <p class="summary-card__label">Function total</p>
                        <p class="summary-card__value">{{ Money::formatMinor($dayTotals['function_total_minor']) }}</p>
                    </article>
                    <article class="summary-card">
                        <p class="summary-card__label">Paid</p>
                        <p class="summary-card__value">{{ Money::formatMinor($dayTotals['paid_total_minor']) }}</p>
                    </article>
                    <article class="summary-card">
                        <p class="summary-card__label">Pending</p>
                        <p class="summary-card__value">{{ Money::formatMinor($dayTotals['pending_total_minor']) }}</p>
                    </article>
                </div>
            </section>

            @foreach ($entries as $entry)
                <section class="print-section entry-block">
                    <div class="entry-top">
                        <div>
                            <p class="eyebrow">Function entry</p>
                            <h2 class="entry-title">{{ $entry->name }}</h2>
                            <div class="chip-row">
                                <span class="chip">{{ optional($entry->entry_date)->format('d M Y') }}</span>
                                <span class="chip">{{ $entry->venue->name }}</span>
                                <span class="chip">{{ $entry->packages_count }} packages</span>
                                <span class="chip">{{ $entry->attachments_count }} files</span>
                            </div>
                            <p class="entry-notes">{{ $entry->notes ?: 'No notes recorded for this function entry.' }}</p>
                        </div>

                        <div class="entry-metrics">
                            <article class="entry-metric">
                                <p class="entry-metric__label">Package total</p>
                                <p class="entry-metric__value">{{ Money::formatMinor($entry->package_total_minor) }}</p>
                            </article>
                            <article class="entry-metric">
                                <p class="entry-metric__label">Extra charges</p>
                                <p class="entry-metric__value">{{ Money::formatMinor($entry->extra_charge_total_minor) }}</p>
                            </article>
                            <article class="entry-metric">
                                <p class="entry-metric__label">Discounts</p>
                                <p class="entry-metric__value">{{ Money::formatMinor($entry->discount_total_minor) }}</p>
                            </article>
                            <article class="entry-metric entry-metric--accent">
                                <p class="entry-metric__label">Function total</p>
                                <p class="entry-metric__value">{{ Money::formatMinor($entry->function_total_minor) }}</p>
                            </article>
                            <article class="entry-metric">
                                <p class="entry-metric__label">Paid</p>
                                <p class="entry-metric__value">{{ Money::formatMinor($entry->paid_total_minor) }}</p>
                            </article>
                            <article class="entry-metric">
                                <p class="entry-metric__label">Pending</p>
                                <p class="entry-metric__value">{{ Money::formatMinor($entry->pending_total_minor) }}</p>
                            </article>
                        </div>
                    </div>

                    <div class="table-section">
                        <div class="table-section__title">Packages and service lines</div>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 21%">Package</th>
                                    <th style="width: 30%">Service line</th>
                                    <th style="width: 10%">Persons</th>
                                    <th style="width: 12%">Rate</th>
                                    <th style="width: 12%">Extra charge</th>
                                    <th style="width: 15%">Line total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($entry->packages as $package)
                                    @php $serviceLineCount = max($package->serviceLines->count(), 1); @endphp
                                    @forelse ($package->serviceLines as $lineIndex => $serviceLine)
                                        <tr>
                                            @if ($lineIndex === 0)
                                                <td rowspan="{{ $serviceLineCount }}">
                                                    <div class="cell-title">{{ $package->name_snapshot }}</div>
                                                    <div class="cell-muted">{{ $package->code_snapshot }}</div>
                                                    <div class="cell-title" style="margin-top: 8px;">{{ Money::formatMinor($package->total_minor) }}</div>
                                                </td>
                                            @endif
                                            <td>
                                                <div class="cell-title">{{ $serviceLine->item_name_snapshot }}</div>
                                                <div class="cell-muted">{{ $serviceLine->notes ?: 'No notes' }}</div>
                                                @if ($serviceLine->service?->attachments?->isNotEmpty())
                                                    <div class="link-list" style="margin-top: 8px;">
                                                        @foreach ($serviceLine->service->attachments as $attachment)
                                                            <div>
                                                                <div class="cell-muted" style="margin-top: 0;">{{ $attachment->original_name }}</div>
                                                                @if ($attachment->canPreviewInline())
                                                                    <a href="{{ route($attachmentPreviewRoute, [$entry, $attachment]) }}" target="_blank">Open</a>
                                                                    <span> | </span>
                                                                @endif
                                                                <a href="{{ route($attachmentDownloadRoute, [$entry, $attachment]) }}">Download</a>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td>{{ $serviceLine->usesPersonsField() ? $serviceLine->persons : 'No persons' }}</td>
                                            <td>{{ Money::formatMinor($serviceLine->rate_minor) }}</td>
                                            <td>{{ Money::formatMinor($serviceLine->extra_charge_minor) }}</td>
                                            <td>{{ Money::formatMinor($serviceLine->line_total_minor) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td><span class="cell-title">{{ $package->name_snapshot }}</span></td>
                                            <td colspan="5">No service lines recorded.</td>
                                        </tr>
                                    @endforelse
                                @empty
                                    <tr>
                                        <td colspan="6">No packages recorded for this function entry.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @foreach ([
                        ['title' => 'Extra charges', 'rows' => $entry->extraCharges, 'empty' => 'No extra charges recorded.'],
                        ['title' => 'Installments', 'rows' => $entry->installments, 'empty' => 'No installments recorded.'],
                        ['title' => 'Discounts', 'rows' => $entry->discounts, 'empty' => 'No discounts recorded.'],
                    ] as $section)
                        <div class="table-section">
                            <div class="table-section__title">{{ $section['title'] }}</div>
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 13%">Date</th>
                                        <th style="width: 18%">Name</th>
                                        <th style="width: 10%">Mode</th>
                                        <th style="width: 11%">Amount</th>
                                        <th style="width: 20%">Notes</th>
                                        <th style="width: 8%">Files</th>
                                        <th style="width: 20%">Links</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($section['rows'] as $row)
                                        <tr>
                                            <td>{{ optional($row->entry_date)->format('d M Y') }}</td>
                                            <td><span class="cell-title">{{ $row->name }}</span></td>
                                            <td>{{ strtoupper((string) $row->mode) }}</td>
                                            <td>{{ Money::formatMinor($row->amount_minor) }}</td>
                                            <td>{{ $row->note ?: 'No notes' }}</td>
                                            <td>{{ $row->attachments->count() }}</td>
                                            <td>
                                                @if ($row->attachments->isEmpty())
                                                    <span class="cell-muted" style="margin-top: 0;">No attachments</span>
                                                @else
                                                    <div class="link-list">
                                                        @foreach ($row->attachments as $attachment)
                                                            <div>
                                                                <div class="cell-title">{{ $attachment->original_name }}</div>
                                                                @if ($attachment->canPreviewInline())
                                                                    <a href="{{ route($attachmentPreviewRoute, [$entry, $attachment]) }}" target="_blank">Open</a>
                                                                    <span> | </span>
                                                                @endif
                                                                <a href="{{ route($attachmentDownloadRoute, [$entry, $attachment]) }}">Download</a>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7">{{ $section['empty'] }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endforeach

                    <div class="table-section">
                        <div class="table-section__title">Base attachments</div>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 34%">File name</th>
                                    <th style="width: 26%">MIME type</th>
                                    <th style="width: 20%">Preview</th>
                                    <th style="width: 20%">Download</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($entry->attachments as $attachment)
                                    <tr>
                                        <td><span class="cell-title">{{ $attachment->original_name }}</span></td>
                                        <td>{{ $attachment->mime_type }}</td>
                                        <td>
                                            @if ($attachment->canPreviewInline())
                                                <a href="{{ route($attachmentPreviewRoute, [$entry, $attachment]) }}" target="_blank">Open</a>
                                            @else
                                                <span class="cell-muted" style="margin-top: 0;">Not previewable</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route($attachmentDownloadRoute, [$entry, $attachment]) }}">Download</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">No base attachments recorded for this function entry.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach

            <section class="print-section entry-block">
                <p class="eyebrow">Terms and conditions</p>
                <div class="terms-box">
                    {!! nl2br(e($printSettings->function_terms_and_conditions)) !!}
                </div>

                <div class="signature-grid">
                    <div class="signature-box">
                        <p class="eyebrow">Customer signature</p>
                        <div class="signature-line"></div>
                        <div class="signature-meta">
                            <span>Customer Signature</span>
                            <span>Date: ____________________</span>
                        </div>
                    </div>

                    <div class="signature-box">
                        <p class="eyebrow">Manager signature</p>
                        <div class="signature-line"></div>
                        <div class="signature-meta">
                            <span>Manager Signature</span>
                            <span>Date: ____________________</span>
                        </div>
                    </div>
                </div>
                <div class="print-footer-space"></div>
            </section>
        </div>

        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    </body>
</html>
