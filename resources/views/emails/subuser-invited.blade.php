@extends('emails.layouts.branded')

@section('title', 'Invitație societăți — DateConta Facturare')
@section('preheader', $inviter->name.' v-a invitat pe societățile administrate în DateConta Facturare.')
@section('eyebrow', 'Invitație')

@section('headline')
    Invitație pe<br>
    <span style="color:#ffb84d;">societățile colegului</span>
@endsection

@section('body')
@php
    $inviterName = trim((string) $inviter->name) ?: 'Un utilizator';
    $companyName = trim((string) $inviterCompanyName) ?: 'societatea sa';
@endphp
    <p style="margin:0 0 14px 0;">Salut{{ filled($recipient->name) ? ', '.$recipient->name : '' }},</p>
    <p style="margin:0 0 14px 0;">
        <strong style="color:#0a3440;">{{ $inviterName }}</strong> de la
        <strong style="color:#0a3440;">{{ $companyName }}</strong>
        v-a invitat să vă alăturați societăților pe care le administrează în
        <strong>DateConta Facturare</strong>.
    </p>
    <p style="margin:0 0 14px 0;">
        Contul dvs. existent rămâne neschimbat. Vă autentificați ca de obicei la
        <a href="{{ $loginUrl }}" style="color:#0f4c5c;font-weight:700;">{{ $loginUrl }}</a>
        cu emailul și parola pe care le folosiți deja
        (<span style="font-family:Consolas,'Courier New',monospace;">{{ $recipient->email }}</span>).
        În selectorul de societate vor apărea și firmele la care ați primit acces.
    </p>

    <p style="margin:18px 0 10px 0;">
        Societăți și drepturi alocate de {{ $inviterName }}:
    </p>
    @include('emails.partials.subuser-access-list', ['accessSummary' => $accessSummary])

    <p style="margin:18px 0 0 0;">
        Pe aceste firme lucrați ca utilizator secundar: vedeți și modificați doar ce v-a permis
        {{ $inviterName }}. Contul dvs. principal (societățile pe care le administrați dvs.) nu este afectat.
        Invitația poate fi revocată oricând de către {{ $inviterName }}, fără a vă șterge contul.
    </p>

    <p style="margin:22px 0 0 0;font-size:15px;color:#0a3440;">
        Cu drag,<br>
        <strong>Echipa DateConta</strong>
    </p>
@endsection
