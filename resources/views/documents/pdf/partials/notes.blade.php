@php
    $parts = [];
    $docNotes = trim((string) ($document->notes ?? ''));
    if ($docNotes !== '') {
        $parts[] = $docNotes;
    }

    // Text societate (Personalizare → Note factură): pe facturi emise.
    // Concatenat după mențiunea documentului, ca să nu dispară când factura are deja notes.
    $companyNotes = trim((string) ($document->company->invoice_notes ?? ''));
    if ($companyNotes !== '' && $document->type === 'invoice' && ! in_array($companyNotes, $parts, true)) {
        $parts[] = $companyNotes;
    }

    // Fallback vechi: pe alte tipuri, nota societății doar dacă documentul n-are notes.
    if ($companyNotes !== '' && $document->type !== 'invoice' && $docNotes === '') {
        $parts[] = $companyNotes;
    }
@endphp
@if($parts !== [])
<p class="notes">{!! nl2br(e(implode("\n\n", $parts))) !!}</p>
@endif
