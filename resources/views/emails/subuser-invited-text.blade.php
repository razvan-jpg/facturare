Salut{{ filled($recipient->name) ? ', '.$recipient->name : '' }},

{{ $inviter->name }} de la {{ $inviterCompanyName }} v-a invitat să vă alăturați societăților pe care le administrează în DateConta Facturare.

Contul dvs. existent rămâne neschimbat. Autentificare: {{ $loginUrl }}
Email: {{ $recipient->email }}
(folosiți parola pe care o aveți deja)

Societăți și drepturi alocate:

@foreach($accessSummary as $row)
- {{ $row['company'] }}@if(!empty($row['cui'])) (CUI {{ $row['cui'] }})@endif
@foreach($row['rights'] as $right)
  · {{ $right }}
@endforeach

@endforeach

Pe aceste firme lucrați ca utilizator secundar. Contul dvs. principal nu este afectat.
Invitația poate fi revocată de {{ $inviter->name }} fără a vă șterge contul.

Cu drag,
Echipa DateConta
