@php
    $t = $labels ?? [];
    $clientId = (string) ($document->client_cui ?? '');
    $isPersonId = ($document->client?->type === 'person')
        || (strlen(preg_replace('/\D+/', '', $clientId) ?? '') === 13);
    $idLabel = $isPersonId ? ($t['cnp'] ?? 'CNP') : ($t['cui'] ?? 'CUI');
@endphp
<strong>{{ $t['client'] ?? 'Client' }}:</strong> {{ $document->client_name }}<br>
@if($clientId !== '' || $document->client_reg_com)
    {{ $idLabel }} {{ $clientId }}@if($document->client_reg_com) · {{ $document->client_reg_com }}@endif<br>
@endif
{{ $document->client_address }}
