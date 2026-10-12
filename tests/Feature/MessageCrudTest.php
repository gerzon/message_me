<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->followingRedirects()->get('/');

        $response
            ->assertOk()
            ->assertSee('Mini Messenger');
    }

    public function test_a_message_can_be_created_updated_and_deleted(): void
    {
        $conversation = $this->selectConversation();

        $this->post(route('messages.store'), [
            'sender' => 'Sam',
            'body' => 'Hello, messenger!',
        ])->assertRedirect(route('messages.index'));

        $message = Message::query()->sole();

        $this->assertSame('Sam', $message->sender);
        $this->assertSame('Hello, messenger!', $message->body);
        $this->assertSame($conversation->id, $message->conversation_id);
        $this->get(route('messages.index'))->assertSee('Hello, messenger!');
        $this->get(route('messages.edit', $message))
            ->assertOk()
            ->assertSee('Hello, messenger!');

        $this->put(route('messages.update', $message), [
            'sender' => 'Alex',
            'body' => 'Updated message',
        ])->assertRedirect(route('messages.index'));

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender' => 'Alex',
            'body' => 'Updated message',
        ]);

        $this->delete(route('messages.destroy', $message))
            ->assertRedirect(route('messages.index'));

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_message_fields_are_required_and_limited(): void
    {
        $this->selectConversation();

        $this->followingRedirects()
            ->from(route('messages.create'))
            ->post(route('messages.store'), [
                'sender' => '',
                'body' => str_repeat('x', 2001),
            ])
            ->assertOk()
            ->assertSee('The sender field is required.')
            ->assertSee('The body field must not be greater than 2000 characters.')
            ->assertSee('maxlength="2000"', false);

        $this->assertDatabaseCount('messages', 0);
    }

    private function selectConversation(): Conversation
    {
        $conversation = Conversation::create(['code' => 'TEST1234']);
        $this->withSession([
            'conversation_id' => $conversation->id,
            'display_name' => 'Sam',
        ]);

        return $conversation;
    }
}
