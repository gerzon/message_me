<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MessageResource::collection(
            Message::query()
                ->whereNull('conversation_id')
                ->latest()
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $message = Message::create($request->validate([
            'sender' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:2000'],
        ]));

        return (new MessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Message $message): MessageResource
    {
        return new MessageResource($this->findUnconnectedMessage($message));
    }

    public function update(Request $request, Message $message): MessageResource
    {
        $message = $this->findUnconnectedMessage($message);
        $message->update($request->validate([
            'sender' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:2000'],
        ]));

        return new MessageResource($message->refresh());
    }

    public function destroy(Message $message): JsonResponse
    {
        $message = $this->findUnconnectedMessage($message);
        $message->delete();

        return response()->json(status: 204);
    }

    private function findUnconnectedMessage(Message $message): Message
    {
        return Message::query()
            ->whereNull('conversation_id')
            ->findOrFail($message->id);
    }
}
