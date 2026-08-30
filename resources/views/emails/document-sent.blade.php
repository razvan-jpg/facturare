@extends('emails.layouts.branded')

@section('title', $document->typeLabel().' '.$document->number_full)
@section('preheader', \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $bodyText) ?: (__('Document').' '.$document->typeLabel()), 120))
@section('eyebrow', __('Document transmis'))
@section('headline', $document->typeLabel().' '.($document->number_full ?: '#'.$document->id))

@section('body')
    @php
        $paragraphs = preg_split("/\n\s*\n/", trim((string) $bodyText)) ?: [];
    @endphp
    @foreach($paragraphs as $paragraph)
        <p style="margin:0 0 12px 0;white-space:pre-wrap;">{{ $paragraph }}</p>
    @endforeach

    @if(! empty($documentLink))
        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:20px 0 18px 0;">
            <tr>
                <td bgcolor="#0a3440" style="border-radius:10px;">
                    <a href="{{ $documentLink }}"
                       style="display:inline-block;padding:12px 20px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                        {{ __('Vizualizează documentul') }}
                    </a>
                </td>
            </tr>
        </table>
    @endif

    @php
        $paymentLinks = $paymentLinks ?? [];
        $paymentHubUrl = $paymentHubUrl ?? null;
    @endphp
    @if($paymentLinks !== [] || filled($paymentHubUrl))
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 18px 0;background:#f0fdfa;border:1px solid #99f6e4;border-radius:12px;">
            <tr>
                <td style="padding:16px 18px;">
                    <div style="font-size:13px;font-weight:800;color:#0a3440;margin-bottom:10px;">{{ __('Plată cu cardul online') }}</div>
                    @foreach($paymentLinks as $link)
                        <a href="{{ $link['url'] }}"
                           style="display:inline-block;margin:0 8px 8px 0;padding:10px 14px;background:#0f766e;color:#fff;text-decoration:none;border-radius:8px;font-size:13px;font-weight:700;">
                            {{ $link['short'] }}
                        </a>
                    @endforeach
                    @if(filled($paymentHubUrl) && count($paymentLinks) !== 1)
                        <div style="margin-top:6px;font-size:12px;color:#334e68;">
                            {{ __('Sau alege procesatorul:') }}
                            <a href="{{ $paymentHubUrl }}" style="color:#0f766e;font-weight:700;">{{ __('pagina de plată') }}</a>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:0 0 8px 0;font-size:13px;color:#627d98;">
        {{ __('Fișierul PDF este direct atașat acestui email.') }}
    </p>
    <p style="margin:0;font-size:13px;color:#627d98;">
        {{ __('Document generat cu :brand.', ['brand' => config('dateconta.brand_name', 'DateConta Facturare')]) }}
    </p>
@endsection

@section('footer')
    {!! __('Mesaj trimis de :company prin :brand.', [
        'company' => '<strong style="color:#fff;">'.e($document->company->name).'</strong>',
        'brand' => e(config('dateconta.brand_name', 'DateConta Facturare')),
    ]) !!}
@endsection
