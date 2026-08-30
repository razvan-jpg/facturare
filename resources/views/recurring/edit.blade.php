@extends('layouts.app')
@php
    $returnPage = max(1, (int) ($returnPage ?? request('page', 1)));
    $listUrl = route('recurring.index', $returnPage > 1 ? ['page' => $returnPage] : []);
@endphp
@section('heading', 'Editează abonament')
@section('subheading', $recurring->displayTitle())
@section('shell_pad', 'px-2 sm:px-3 lg:px-4')
@section('content')
<div class="dc-card p-6 w-full max-w-none">
    <form method="POST" action="{{ route('recurring.update', $recurring) }}" class="dc-doc-editor" novalidate>
        @csrf @method('PUT')
        <input type="hidden" name="return_page" value="{{ $returnPage }}">
        @include('recurring._form')
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="dc-btn-primary">Salvează modificările</button>
            <a href="{{ $listUrl }}" class="dc-btn-secondary">Înapoi la listă</a>
        </div>
    </form>
</div>
@endsection
