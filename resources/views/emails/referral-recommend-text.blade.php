{{ __('Îți recomand călduros DateConta Facturare') }}

{{ __('Salut,') }}

{{ __('Sunt :sender de la :company. Îți recomand călduros DateConta Facturare — facturi, proforme, avize, chitanțe, e-Factura ANAF.', [
    'sender' => $sender->name,
    'company' => $company->name,
]) }}

{{ __('COD PROMOȚIONAL (important):') }}
{{ $promoCode }}

{{ __('Sfat: la înregistrare / la crearea societății folosește acest cod și vom avea amândoi de câștigat perioade promoționale gratuite.') }}

{{ __('Cum folosești codul:') }}
1. {{ __('Creează contul:') }} {{ $registerUrl }}
2. {{ __('La „Adaugă societate”, bifează că ai un cod promoțional') }}
3. {{ __('Introdu :code și salvează', ['code' => $promoCode]) }}

{{ __('Ce câștigăm:') }}
- {{ __('Tu: +:days zile la acces', ['days' => (int) $inviteeBonusDays]) }}
- {{ __('Eu: +:months lună la fiecare :every societăți aduse', [
    'months' => (int) $referrerBonusMonths,
    'every' => (int) $referrerEvery,
]) }}

{{ __('Cu drag,') }}
{{ $sender->name }}
{{ $company->name }}
