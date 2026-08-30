@extends('layouts.app')
@section('heading', 'Emitere › '.(\App\Models\Document::TYPE_LABELS[$type] ?? 'Document'))
@section('shell_pad', 'px-2 sm:px-3 lg:px-4')
@section('content')
<form method="POST" action="{{ route('documents.store') }}" class="dc-doc-editor">
@csrf
@include('documents._form')
</form>
@endsection
