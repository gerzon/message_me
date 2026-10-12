@extends('layouts.app')

@section('title', 'Edit message')

@section('content')
    <div class="form-page">
        <a class="back-link" href="{{ route('messages.index') }}">← Back to messages</a>
        <div class="form-heading">
            <p class="eyebrow">MAKE IT JUST RIGHT</p>
            <h1>Edit your message</h1>
            <p>Update the note below and save your changes.</p>
        </div>

        @include('messages._form', [
            'action' => route('messages.update', $messageRecord),
            'isEditing' => true,
            'submitLabel' => 'Save changes',
        ])
    </div>
@endsection
