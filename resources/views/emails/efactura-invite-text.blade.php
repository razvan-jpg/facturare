Autorizare e-Factura SPV — DateConta Facturare

Ați fost invitat să autorizați societatea {{ $company->name }}@if($company->cui) (CUI {{ $company->cui }})@endif în Spațiul Privat Virtual ANAF, pentru trimiterea facturilor electronice din DateConta Facturare.

Aveți nevoie de certificat digital înrolat în SPV, cu drepturi pe acest CUI.

Deschideți linkul de autorizare:
{{ $url }}

Linkul expiră la {{ dc_datetime($invite->expires_at) }}.

Dacă nu vă așteptați la acest mesaj, îl puteți ignora.

Promisiunea DateConta: cel mai bun și cel mai ieftin soft de facturare.
După perioada de grație: de la 1,99 EUR / lună + TVA.

DateConta Facturare
{{ config('app.url') }}
