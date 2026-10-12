<?php

namespace Tests\Feature;

use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConnectionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_show_a_connection_modal_until_a_conversation_is_selected(): void
    {
        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Start a conversation.')
            ->assertSee('Have a connection code?')
            ->assertSee('Create a new conversation')
            ->assertDontSee('id="messages-heading"', false);
    }

    public function test_a_new_conversation_gets_an_eight_character_code_and_becomes_active(): void
    {
        Str::createRandomStringsUsing(fn () => 'a1b2c3d4');

        try {
            $response = $this->post(route('connections.store'), [
                'display_name' => 'Sam',
            ]);
        } finally {
            Str::createRandomStringsNormally();
        }

        $conversation = Conversation::query()->sole();

        $response
            ->assertRedirect(route('messages.index'))
            ->assertSessionHas('conversation_id', $conversation->id)
            ->assertSessionHas('display_name', 'Sam');

        $this->assertSame('A1B2C3D4', $conversation->code);
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'code' => 'A1B2C3D4',
        ]);

        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('A1B2C3D4')
            ->assertSee('Share this code to invite someone');
    }

    public function test_a_valid_code_opens_the_existing_conversation_and_its_messages(): void
    {
        $conversation = Conversation::create(['code' => 'ABCD1234']);
        $message = $conversation->messages()->create([
            'sender' => 'Sam',
            'body' => 'A message from before.',
        ]);

        $response = $this->post(route('connections.join'), [
            'code' => 'abcd1234',
            'display_name' => 'Alex',
        ]);

        $response
            ->assertRedirect(route('messages.index'))
            ->assertSessionHas('conversation_id', $conversation->id)
            ->assertSessionHas('display_name', 'Alex');

        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('A message from before.')
            ->assertSee('ABCD1234')
            ->assertSee('Chatting as Alex');

        $this->assertSame($conversation->id, $message->conversation_id);
    }

    public function test_an_unknown_code_keeps_the_user_on_the_connection_modal(): void
    {
        $this->from(route('messages.index'))
            ->post(route('connections.join'), [
                'code' => 'BAD12345',
                'display_name' => 'Sam',
            ])
            ->assertRedirect(route('messages.index'))
            ->assertSessionHasErrors([
                'code' => 'No conversation was found with that code.',
            ])
            ->assertSessionMissing('conversation_id');

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_a_connection_code_must_be_eight_alphanumeric_characters(): void
    {
        $this->from(route('messages.index'))
            ->post(route('connections.join'), [
                'code' => 'short!',
                'display_name' => 'Sam',
            ])
            ->assertRedirect(route('messages.index'))
            ->assertSessionHasErrors('code')
            ->assertSessionMissing('conversation_id');

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_messages_and_message_actions_are_scoped_to_the_selected_conversation(): void
    {
        $selectedConversation = Conversation::create(['code' => 'ABCD1234']);
        $otherConversation = Conversation::create(['code' => 'WXYZ9876']);
        $selectedConversation->messages()->create([
            'sender' => 'Sam',
            'body' => 'Visible in this conversation.',
        ]);
        $otherMessage = $otherConversation->messages()->create([
            'sender' => 'Alex',
            'body' => 'Not visible in this conversation.',
        ]);

        $this->post(route('connections.join'), [
            'code' => $selectedConversation->code,
            'display_name' => 'Sam',
        ])
            ->assertRedirect(route('messages.index'));

        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Visible in this conversation.')
            ->assertDontSee('Not visible in this conversation.');

        $this->get(route('messages.edit', $otherMessage))
            ->assertNotFound();
    }

    public function test_a_conversation_code_can_be_updated(): void
    {
        $conversation = Conversation::create(['code' => 'ABCD1234']);
        $this->withSession([
            'conversation_id' => $conversation->id,
            'display_name' => 'Sam',
        ]);

        $this->put(route('conversations.update', $conversation), [
            'code' => 'NEWCODE1',
        ])->assertRedirect(route('messages.index'));

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'code' => 'NEWCODE1',
        ]);
    }

    public function test_a_conversation_can_be_deleted_and_clears_the_session(): void
    {
        $conversation = Conversation::create(['code' => 'ABCD1234']);
        $this->withSession([
            'conversation_id' => $conversation->id,
            'display_name' => 'Sam',
        ]);

        $this->delete(route('conversations.destroy', $conversation))
            ->assertRedirect(route('messages.index'))
            ->assertSessionMissing('conversation_id')
            ->assertSessionMissing('display_name');

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    public function test_leaving_a_conversation_returns_to_the_connection_modal(): void
    {
        $conversation = Conversation::create(['code' => 'ABCD1234']);
        $this->withSession([
            'conversation_id' => $conversation->id,
            'display_name' => 'Sam',
        ]);

        $this->post(route('connections.leave'))
            ->assertRedirect(route('messages.index'))
            ->assertSessionMissing('conversation_id')
            ->assertSessionMissing('display_name');

        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Start a conversation.');
    }

    public function test_new_messages_use_the_display_name_saved_in_the_session(): void
    {
        $conversation = Conversation::create(['code' => 'ABCD1234']);
        $conversation->messages()->create([
            'sender' => 'Alex',
            'body' => 'A message from the other person.',
        ]);

        $this->withSession([
            'conversation_id' => $conversation->id,
            'display_name' => 'Sam',
        ]);

        $this->post(route('messages.store'), [
            'sender' => 'Alex',
            'body' => 'A message from me.',
        ])->assertRedirect(route('messages.index'));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender' => 'Sam',
            'body' => 'A message from me.',
        ]);

        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('chat-message-sent', false)
            ->assertSee('chat-message-received', false);
    }
}
