@extends('legal._layout')

@section('legal')
<h2>{{ __('Documente legale') }}</h2>
<p class="help-lead">
    {!! __('Aici găsești termenii de folosire, politicile de confidențialitate, livrare, anulare și GDPR aplicabile serviciului <strong>:brand</strong>.', ['brand' => e($brand)]) !!}
</p>

<div class="help-note">
    {!! __('Operatorul platformei este <strong>:name</strong>, CUI <strong>:cui</strong>, :reg_com, cu sediul în :address, :city, :county, :country. Contact: <a href="mailto::email">:email</a>.', [
        'name' => e($operator['name']),
        'cui' => e($operator['cui']),
        'reg_com' => e($operator['reg_com']),
        'address' => e($operator['address']),
        'city' => e($operator['city']),
        'county' => e($operator['county']),
        'country' => e($operator['country']),
        'email' => e($contact),
    ]) !!}
</div>

<p>
    {{ __('Prin crearea unui cont, plasarea unei comenzi de abonament sau utilizarea aplicației, confirmi că ai citit și înțeles documentele de mai jos. Te rugăm să le consulți integral înainte de a accepta termenii la înregistrare sau la plata abonamentului.') }}
</p>

<h3>{{ __('Cuprins') }}</h3>
<ol>
    @foreach($pages as $key => $sec)
        <li>
            <a href="{{ route('legal.show', $key) }}" class="text-teal-800 font-semibold hover:underline">{{ __($sec['title']) }}</a>
            <span class="text-slate-500"> — {{ __($sec['subtitle']) }}</span>
        </li>
    @endforeach
</ol>

<h3>{{ __('Informații generale') }}</h3>
<ul>
    <li>{{ __('Serviciul este un software SaaS de facturare online, accesibil prin internet la domeniul factura.dateconta.ro.') }}</li>
    <li>{{ __('Documentele legale se aplică relației dintre Operator și Utilizatorul care creează un cont pe platformă.') }}</li>
    <li>{{ __('Actualizările sunt publicate pe aceste pagini; data ultimei actualizări apare pe fiecare document.') }}</li>
    <li>{!! __('Pentru clarificări: <a href="mailto::email">:email</a>.', ['email' => e($contact)]) !!}</li>
</ul>

<div class="help-warn">
    {{ __('Conținutul are caracter informativ și contractual pentru utilizarea platformei. Nu constituie consultanță juridică individuală. Pentru situații speciale, consultă un specialist.') }}
</div>
@endsection
