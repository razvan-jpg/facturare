Salut{{ filled($recipient->name) ? ', '.$recipient->name : '' }},

{{ $creator->name }} de la {{ $creatorCompanyName }} v-a creat ca utilizator al aplicației DateConta Facturare.

Autentificare: {{ $loginUrl }}
Email (utilizator): {{ $recipient->email }}
Parolă: {{ $plainPassword }}

Vă recomandăm să schimbați parola după prima autentificare, din Contul meu.

Aveți acces la următoarele societăți, cu drepturile aferente fiecăreia:

@foreach($accessSummary as $row)
- {{ $row['company'] }}@if(!empty($row['cui'])) (CUI {{ $row['cui'] }})@endif
@foreach($row['rights'] as $right)
  · {{ $right }}
@endforeach

@endforeach

Dacă aveți întrebări, contactați-l pe {{ $creator->name }} sau scrieți-ne la {{ config('dateconta.contact_email') }}.

Cu drag,
Echipa DateConta
