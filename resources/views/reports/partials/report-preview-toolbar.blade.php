@php
    $pdfUrl = $pdfUrl ?? '#';
    $title = $title ?? 'Raport';
@endphp
<div class="dc-report-toolbar no-print">
    <div class="dc-report-toolbar-title">{{ $title }}</div>
    <div class="dc-report-toolbar-actions">
        <a href="{{ $pdfUrl }}" class="dc-btn-primary">Export PDF</a>
        <button type="button" class="dc-btn-secondary" onclick="window.print()">Print</button>
        <button type="button" class="dc-btn-secondary" onclick="window.close()">{{ __('Închide') }}</button>
    </div>
</div>
