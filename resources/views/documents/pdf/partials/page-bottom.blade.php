@php($t = $labels ?? [])
<div class="pdf-bottom">
    @include('documents.pdf.partials.notes')
    @include('documents.pdf.partials.footer-meta')
    <table class="pdf-bottom-table">
        <tr>
            <td class="pdf-bottom-left">
                @include('documents.pdf.partials.signature')
            </td>
            <td class="pdf-bottom-right">
                @include('documents.pdf.partials.totals')
            </td>
        </tr>
    </table>
    <p class="footer">
        <a href="{{ rtrim((string) config('app.url'), '/') }}/" class="footer-brand-link">{{ $t['generated'] ?? 'Document generat cu DateConta Facturare' }}</a>
    </p>
</div>
</div>
