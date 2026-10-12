<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function edit(Conversation $conversation): View|RedirectResponse
    {
        $currentConversation = $this->currentConversation();

        if (! $currentConversation) {
            return redirect()->route('messages.index');
        }

        abort_unless($currentConversation->id === $conversation->id, 404);

        return view('conversations.edit', compact('conversation'));
    }

    public function update(Request $request, Conversation $conversation): RedirectResponse
    {
        $currentConversation = $this->currentConversation();

        if (! $currentConversation) {
            return redirect()->route('messages.index');
        }

        abort_unless($currentConversation->id === $conversation->id, 404);

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:8', 'alpha_num', 'unique:conversations,code,'.$conversation->id],
        ]);

        $conversation->update([
            'code' => Str::upper($validated['code']),
        ]);

        return redirect()
            ->route('messages.index')
            ->with('status', 'Your conversation code was updated.');
    }

    public function destroy(Request $request, Conversation $conversation): RedirectResponse
    {
        $currentConversation = $this->currentConversation();

        if (! $currentConversation) {
            return redirect()->route('messages.index');
        }

        abort_unless($currentConversation->id === $conversation->id, 404);

        $conversation->delete();
        $request->session()->forget(['conversation_id', 'display_name']);

        return redirect()
            ->route('messages.index')
            ->with('status', 'Your conversation was deleted.');
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
