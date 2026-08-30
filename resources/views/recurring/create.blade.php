@extends('layouts.app')
@section('heading', 'Abonament nou')
@section('subheading', 'Configurează o factură care se emite automat după frecvența aleasă')
@section('shell_pad', 'px-2 sm:px-3 lg:px-4')
@section('content')
<div class="dc-card p-6 w-full max-w-none">
    <form method="POST" action="{{ route('recurring.store') }}" class="dc-doc-editor" novalidate>
        @csrf
        @include('recurring._form')
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="dc-btn-primary">Salvează abonamentul</button>
            <a href="{{ route('recurring.index') }}" class="dc-btn-secondary">{{ __('Anulează') }}</a>
        </div>
    </form>
</div>
@endsection
