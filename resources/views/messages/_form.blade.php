<form class="message-form" action="{{ $action }}" method="POST">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <div class="field">
        <label for="sender">Your name</label>
        <input id="sender" name="sender" type="text" value="{{ old('sender', $messageRecord->sender) }}"
               maxlength="80" autocomplete="name" required autofocus>
        @error('sender')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <div class="label-row">
            <label for="body">Your message</label>
            <span class="field-hint">Up to 2,000 characters</span>
        </div>
        <textarea id="body" name="body" rows="6" maxlength="2000" required>{{ old('body', $messageRecord->body) }}</textarea>
        @error('body')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-actions">
        <a class="button button-secondary" href="{{ route('messages.index') }}">Cancel</a>
        <button class="button button-primary" type="submit">{{ $submitLabel }} <span aria-hidden="true">→</span></button>
    </div>
</form>
