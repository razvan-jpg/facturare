@extends('emails.layouts.branded')

@section('title', 'Notificare restanțe — '.$company->name)
@section('preheader', 'Restanțe de plată la '.$company->name)
@section('eyebrow', 'Notificare restanțe')
@section('headline', 'Bună ziua, '.$client->name)

@section('body')
    <p style="margin:0 0 14px;">
        Vă informăm că la <strong style="color:#0a3440;">{{ $company->name }}</strong> există restanțe de plată.
        Vă rugăm să regularizați situația în cel mai scurt timp.
    </p>

    @if(in_array($scope, ['balance', 'both'], true))
        <div style="background:#fff7ed;border:1px solid #fdba74;border-radius:12px;padding:16px 18px;margin:0 0 18px;">
            <div style="font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#9a3412;font-weight:700;">Sold restant</div>
            <div style="font-size:28px;font-weight:800;color:#7c2d12;margin-top:4px;">
                {{ number_format($balance, 2, ',', '.') }} RON
            </div>
            <div style="font-size:13px;color:#9a3412;margin-top:4px;">
                {{ $overdueInvoices->count() }} factur{{ $overdueInvoices->count() === 1 ? 'ă restantă' : 'i restante' }}
            </div>
        </div>
    @endif

    @if(in_array($scope, ['invoices', 'both'], true))
        <div style="font-size:13px;font-weight:700;color:#0f4c5c;margin:0 0 8px;text-transform:uppercase;letter-spacing:.04em;">Facturi restante</div>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:13px;margin-bottom:18px;">
            <tr style="background:#f0f4f8;color:#486581;">
                <th align="left" style="padding:8px;">Factură</th>
                <th align="left" style="padding:8px;">Scadență</th>
                <th align="right" style="padding:8px;">Rest</th>
            </tr>
            @foreach($overdueInvoices as $invoice)
                <tr>
                    <td style="padding:8px;border-top:1px solid #d9e2ec;">{{ $invoice->number_full }}</td>
                    <td style="padding:8px;border-top:1px solid #d9e2ec;">{{ dc_date($invoice->due_date) }}</td>
                    <td align="right" style="padding:8px;border-top:1px solid #d9e2ec;font-weight:700;">
                        {{ number_format($invoice->remainingAmount(), 2, ',', '.') }} {{ $invoice->currency }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($includeStatement)
        <p style="margin:0 0 14px;font-size:14px;line-height:1.5;">
            Atașat găsiți <strong>fișa de client</strong> cu situația facturilor deschise.
        </p>
    @endif

    @php($accounts = $company->invoiceBankAccounts())
    @if($accounts->isNotEmpty() || $company->iban)
        <div style="font-size:13px;color:#486581;line-height:1.5;margin-top:8px;">
            <strong style="color:#0f4c5c;">Date plată:</strong><br>
            @forelse($accounts as $account)
                IBAN {{ $account->iban }}@if($account->bank?->name) · {{ $account->bank->name }}@endif<br>
            @empty
                IBAN {{ $company->iban }} · {{ $company->bank_name }}
            @endforelse
        </div>
    @endif
@endsection

@section('footer')
    Mesaj trimis de <strong style="color:#fff;">{{ $company->name }}</strong>
    prin {{ $brand ?? config('dateconta.brand_name', 'DateConta Facturare') }}.<br>
    @if($company->email)Contact: {{ $company->email }}@endif
@endsection
