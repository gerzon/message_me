<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConnectionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:80'],
        ]);

        do {
            $code = Str::upper(Str::random(8));
        } while (Conversation::query()->where('code', $code)->exists());

        $conversation = Conversation::create(['code' => $code]);

        $request->session()->put([
            'conversation_id' => $conversation->id,
            'display_name' => trim($validated['display_name']),
        ]);

        return redirect()
            ->route('messages.index')
            ->with('status', 'Your new conversation is ready. Share the code below to invite someone.');
    }

    public function join(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:8', 'alpha_num'],
            'display_name' => ['required', 'string', 'max:80'],
        ]);

        $conversation = Conversation::query()
            ->where('code', Str::upper($validated['code']))
            ->first();

        if (! $conversation) {
            return back()
                ->withErrors(['code' => 'No conversation was found with that code.'])
                ->withInput();
        }

        $request->session()->put([
            'conversation_id' => $conversation->id,
            'display_name' => trim($validated['display_name']),
        ]);

        return redirect()
            ->route('messages.index')
            ->with('status', 'You joined the conversation.');
    }

    public function leave(Request $request): RedirectResponse
    {
        $request->session()->forget(['conversation_id', 'display_name']);

        return redirect()->route('messages.index');
    }
}
