@extends('layouts.app')
@section('heading', 'Editează '.(\App\Models\Document::TYPE_LABELS[$document->type] ?? 'document'))
@section('shell_pad', 'px-2 sm:px-3 lg:px-4')
@section('content')
<form method="POST" action="{{ route('documents.update', $document) }}" class="dc-doc-editor">
@csrf @method('PUT')
@include('documents._form', ['document' => $document, 'type' => $document->type])
</form>
@endsection
