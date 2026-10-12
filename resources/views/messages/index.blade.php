@extends('layouts.app')

@section('title', $conversation ? 'Messages' : 'Connect')
@section('body-class', 'chat-theme')

@section('content')
    @if (! $conversation)
        <div class="connection-screen">
            <section class="connection-modal" role="dialog" aria-modal="true" aria-labelledby="connection-heading">
                <div class="connection-icon" aria-hidden="true">✳</div>
                <p class="eyebrow">A PRIVATE SPACE FOR TWO</p>
                <h1 id="connection-heading">Start a conversation.</h1>
                <p class="connection-copy">Join a conversation with a code, or create one and share it with someone.</p>

                <form class="connection-join-form" action="{{ route('connections.join') }}" method="POST">
                    @csrf
                    <div class="field">
                        <label for="join-display-name">Your display name</label>
                        <input id="join-display-name" name="display_name" type="text"
                               value="{{ old('display_name') }}" maxlength="80"
                               autocomplete="nickname" placeholder="How should people know you?"
                               aria-invalid="{{ $errors->has('display_name') ? 'true' : 'false' }}" required>
                        @error('display_name')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="field">
                        <label for="connection-code">Have a connection code?</label>
                        <input
                            class="connection-code-input"
                            id="connection-code"
                            name="code"
                            type="text"
                            value="{{ old('code') }}"
                            placeholder="e.g. A1B2C3D4"
                            aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}"
                            minlength="8"
                            maxlength="8"
                            pattern="[A-Za-z0-9]{8}"
                            autocomplete="off"
                            autocapitalize="characters"
                            aria-describedby="connection-code-hint"
                            required
                            autofocus
                        >
                        <span class="field-hint" id="connection-code-hint">Enter the 8-character code you were given.</span>
                        @error('code')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <button class="button button-primary connection-join-button" type="submit">
                        Join conversation <span aria-hidden="true">→</span>
                    </button>

                    <div class="connection-divider"><span>or</span></div>

                    <button class="button button-secondary connection-create-button" type="submit"
                            formaction="{{ route('connections.store') }}" formnovalidate>
                        Create a new conversation <span aria-hidden="true">→</span>
                    </button>
                </form>
            </section>
        </div>
    @else
        @php($lastMessage = $messages->last())
        <section class="chat-workspace" aria-label="Messenger">
            <aside class="chat-sidebar" aria-label="Conversation list">
                <div class="chat-sidebar-header">
                    <h1>Messages</h1>
                    <span class="sidebar-count">1</span>
                </div>

                <div class="chat-sidebar-section">YOUR CONVERSATIONS</div>

                <article class="conversation-preview conversation-preview-active">
                    <div class="chat-avatar" aria-hidden="true">{{ strtoupper(mb_substr($conversation->code, 0, 2)) }}</div>
                    <div class="conversation-preview-copy">
                        <div class="conversation-preview-heading">
                            <h2>Shared conversation</h2>
                            <span class="online-dot" aria-label="Conversation active"></span>
                        </div>
                        <p>{{ $lastMessage?->sender ?? 'Your chat is ready' }}{{ $lastMessage ? ': '.$lastMessage->body : ' · '.$conversation->code }}</p>
                    </div>
                </article>

                <div class="chat-sidebar-bottom">
                    <p>Share this code to invite someone</p>
                    <code>{{ $conversation->code }}</code>
                    <div class="conversation-actions">
                        <a href="{{ route('conversations.edit', $conversation) }}" class="sidebar-switch-button">Change code</a>
                        <form action="{{ route('conversations.destroy', $conversation) }}" method="POST" onsubmit="return confirm('Delete this conversation and all messages in it?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sidebar-switch-button sidebar-delete-button">Delete conversation</button>
                        </form>
                    </div>
                    <form action="{{ route('connections.leave') }}" method="POST">
                        @csrf
                        <button type="submit" class="sidebar-switch-button">Switch conversation</button>
                    </form>
                </div>
            </aside>

            <section class="chat-panel" aria-labelledby="conversation-title">
                <header class="chat-header">
                    <div class="chat-avatar chat-header-avatar" aria-hidden="true">{{ strtoupper(mb_substr($conversation->code, 0, 2)) }}</div>
                    <div class="chat-header-copy">
                        <h2 id="conversation-title">Shared conversation</h2>
                        <p><span class="online-dot"></span> Code {{ $conversation->code }} <span class="chat-header-divider">·</span> Chatting as {{ session('display_name') }}</p>
                    </div>
                    <form action="{{ route('connections.leave') }}" method="POST" class="mobile-switch-form">
                        @csrf
                        <button type="submit" class="mobile-switch-button" aria-label="Switch conversation">Switch</button>
                    </form>
                </header>

                <div class="chat-messages" id="chat-messages" aria-live="polite">
                    @if ($messages->isEmpty())
                        <div class="chat-empty-state">
                            <span aria-hidden="true">✳</span>
                            <h3>Say hello to get started</h3>
                            <p>Your messages will appear here.</p>
                        </div>
                    @else
                        @foreach ($messages as $message)
                            @php($isSent = $message->sender === session('display_name'))
                            <article class="chat-message {{ $isSent ? 'chat-message-sent' : 'chat-message-received' }}">
                                @unless ($isSent)
                                    <span class="chat-message-sender">{{ $message->sender }}</span>
                                @endunless
                                <p class="chat-bubble">{!! nl2br(e($message->body)) !!}</p>
                                <time datetime="{{ $message->created_at->toIso8601String() }}">
                                    {{ $message->created_at->format('g:i A') }}
                                    @if ($isSent)
                                        <span class="sent-check" aria-label="Sent">✓✓</span>
                                    @endif
                                </time>
                            </article>
                        @endforeach
                        @if ($messages->hasPages())
                            <div class="chat-pagination">{{ $messages->links() }}</div>
                        @endif
                    @endif
                </div>

                <form class="chat-composer" action="{{ route('messages.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="sender" value="{{ session('display_name') }}">
                    <label class="sr-only" for="chat-message">Write a message</label>
                    <textarea id="chat-message" name="body" rows="1" maxlength="2000"
                              placeholder="Message..." required>{{ old('body') }}</textarea>
                    <button class="chat-send-button" type="submit" aria-label="Send message" title="Send message">
                        <span aria-hidden="true">➤</span>
                    </button>
                    @error('body')
                        <p class="field-error chat-composer-error">{{ $message }}</p>
                    @enderror
                </form>
            </section>
        </section>
    @endif
@endsection
