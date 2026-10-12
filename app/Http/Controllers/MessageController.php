<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(): View
    {
        $conversation = $this->currentConversation();

        if (! $conversation) {
            return view('messages.index', [
                'conversation' => null,
                'messages' => null,
            ]);
        }

        $messages = $conversation->messages()
            ->latest()
            ->paginate(50);
        $messages->setCollection($messages->getCollection()->reverse()->values());

        return view('messages.index', compact('conversation', 'messages'));
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->currentConversation()) {
            return redirect()->route('messages.index');
        }

        return view('messages.create', ['messageRecord' => new Message]);
    }

    public function store(Request $request): RedirectResponse
    {
        $conversation = $this->currentConversation();

        if (! $conversation) {
            return redirect()->route('messages.index');
        }

        $validated = $request->validate([
            'sender' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:2000'],
        ]);
        $validated['sender'] = session('display_name');

        $conversation->messages()->create($validated);

        return redirect()
            ->route('messages.index')
            ->with('status', 'Your message was sent.');
    }

    public function edit(Message $message): View|RedirectResponse
    {
        $conversation = $this->currentConversation();

        if (! $conversation) {
            return redirect()->route('messages.index');
        }

        $message = $conversation->messages()->findOrFail($message->id);

        return view('messages.edit', ['messageRecord' => $message]);
    }

    public function update(Request $request, Message $message): RedirectResponse
    {
        $conversation = $this->currentConversation();

        if (! $conversation) {
            return redirect()->route('messages.index');
        }

        $message = $conversation->messages()->findOrFail($message->id);
        $message->update($request->validate([
            'sender' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:2000'],
        ]));

        return redirect()
            ->route('messages.index')
            ->with('status', 'Your message was updated.');
    }

    public function destroy(Message $message): RedirectResponse
    {
        $conversation = $this->currentConversation();

        if (! $conversation) {
            return redirect()->route('messages.index');
        }

        $message = $conversation->messages()->findOrFail($message->id);
        $message->delete();

        return redirect()
            ->route('messages.index')
            ->with('status', 'Your message was deleted.');
    }

    private function currentConversation(): ?Conversation
    {
        if (! is_string(session('display_name')) || trim(session('display_name')) === '') {
            return null;
        }

        $conversation = Conversation::query()->find(session('conversation_id'));

        if (! $conversation) {
            session()->forget('conversation_id');
        }

        return $conversation;
    }
}
