@extends('layouts.app')

@section('title', 'Write a message')

@section('content')
    <div class="form-page">
        <a class="back-link" href="{{ route('messages.index') }}">← Back to messages</a>
        <div class="form-heading">
            <p class="eyebrow">YOUR TURN</p>
            <h1>Write a message</h1>
            <p>Keep it kind, keep it you. Your message will appear on the board.</p>
        </div>

        @include('messages._form', [
            'action' => route('messages.store'),
            'isEditing' => false,
            'submitLabel' => 'Send message',
        ])
    </div>
@endsection
