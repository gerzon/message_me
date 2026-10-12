<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_can_be_listed_and_retrieved_as_json(): void
    {
        $message = Message::create([
            'sender' => 'Sam',
            'body' => 'Hello from the API!',
        ]);

        $this->getJson('/api/v1/messages')
            ->assertOk()
            ->assertJsonPath('data.0.id', $message->id)
            ->assertJsonPath('data.0.sender', 'Sam')
            ->assertJsonPath('data.0.body', 'Hello from the API!')
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->getJson("/api/v1/messages/{$message->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $message->id)
            ->assertJsonPath('data.sender', 'Sam');
    }

    public function test_a_message_can_be_created_updated_and_deleted_through_the_api(): void
    {
        $this->postJson('/api/v1/messages', [
            'sender' => 'Sam',
            'body' => 'Created through JSON',
        ])
            ->assertCreated()
            ->assertJsonPath('data.sender', 'Sam')
            ->assertJsonPath('data.body', 'Created through JSON');

        $message = Message::query()->sole();

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender' => 'Sam',
            'body' => 'Created through JSON',
        ]);

        $this->putJson("/api/v1/messages/{$message->id}", [
            'sender' => 'Alex',
            'body' => 'Updated through JSON',
        ])
            ->assertOk()
            ->assertJsonPath('data.sender', 'Alex')
            ->assertJsonPath('data.body', 'Updated through JSON');

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender' => 'Alex',
            'body' => 'Updated through JSON',
        ]);

        $this->deleteJson("/api/v1/messages/{$message->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    public function test_invalid_api_input_returns_json_validation_errors(): void
    {
        $this->postJson('/api/v1/messages', [
            'sender' => '',
            'body' => str_repeat('x', 2001),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sender', 'body']);

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_requesting_a_missing_message_returns_not_found(): void
    {
        $this->getJson('/api/v1/messages/999')
            ->assertNotFound();
    }

    public function test_the_legacy_api_cannot_read_or_modify_conversation_messages(): void
    {
        $conversation = Conversation::create(['code' => 'ABCD1234']);
        $message = $conversation->messages()->create([
            'sender' => 'Sam',
            'body' => 'This belongs to a connection.',
        ]);

        $this->getJson('/api/v1/messages')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/v1/messages/{$message->id}")
            ->assertNotFound();

        $this->putJson("/api/v1/messages/{$message->id}", [
            'sender' => 'Alex',
            'body' => 'Changed through the API',
        ])->assertNotFound();

        $this->deleteJson("/api/v1/messages/{$message->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender' => 'Sam',
            'body' => 'This belongs to a connection.',
        ]);
    }
}
