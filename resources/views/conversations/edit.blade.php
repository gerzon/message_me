@extends('layouts.app')

@section('title', 'Change conversation code')

@section('content')
    <div class="form-page">
        <a class="back-link" href="{{ route('messages.index') }}">← Back to messages</a>
        <div class="form-heading">
            <p class="eyebrow">KEEP IT UNIQUE</p>
            <h1>Change the conversation code</h1>
            <p>Use a new 8-character code to keep this chat invite link fresh.</p>
        </div>

        <form class="message-form" action="{{ route('conversations.update', $conversation) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="code">Conversation code</label>
                <input id="code" name="code" type="text" value="{{ old('code', $conversation->code) }}"
                       maxlength="8" minlength="8" autocomplete="off" autocapitalize="characters"
                       pattern="[A-Za-z0-9]{8}" required autofocus>
                <span class="field-hint">Enter exactly 8 letters or numbers.</span>
                @error('code')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-actions">
                <a class="button button-secondary" href="{{ route('messages.index') }}">Cancel</a>
                <button class="button button-primary" type="submit">Save changes <span aria-hidden="true">→</span></button>
            </div>
        </form>
    </div>
@endsection
