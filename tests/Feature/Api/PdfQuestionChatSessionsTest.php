<?php

namespace Tests\Feature\Api;

use App\Models\ChatHistory;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PdfQuestionChatSessionsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->headers = [
            'api-key' => 'BANKING-CO-EFFICIENT',
            'platform' => 'WEB',
            'Accept' => 'application/json',
        ];
    }

    /** @test */
    public function guests_cannot_access_chat_session_endpoints()
    {
        $this->postJson('/api/pdf/sessions/list', [], $this->headers)->assertStatus(401);
        $this->postJson('/api/pdf/sessions/create', [], $this->headers)->assertStatus(401);
        $this->postJson('/api/pdf/sessions/rename', [], $this->headers)->assertStatus(401);
        $this->postJson('/api/pdf/sessions/delete', [], $this->headers)->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_create_chat_session()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/pdf/sessions/create', ['title' => 'Custom Title'], $this->headers);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'session' => ['id', 'user_id', 'title', 'created_at', 'updated_at'],
            'message'
        ]);

        $this->assertDatabaseHas('chat_sessions', [
            'user_id' => $this->user->id,
            'title' => 'Custom Title'
        ]);
    }

    /** @test */
    public function authenticated_user_can_list_chat_sessions()
    {
        // Create sessions
        $session1 = ChatSession::create(['user_id' => $this->user->id, 'title' => 'Session 1']);
        $session2 = ChatSession::create(['user_id' => $this->user->id, 'title' => 'Session 2']);

        // Add a message to session 1 to test message count
        ChatHistory::create([
            'user_id' => $this->user->id,
            'chat_session_id' => $session1->id,
            'question' => 'Hello',
            'answer' => 'Hi there',
            'suggestions' => [],
            'images' => [],
            'reference_pages' => []
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/pdf/sessions/list', [], $this->headers);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'sessions');
        
        $sessionsData = $response->json('sessions');
        // Ordered by updated_at desc (session 1 was updated last due to message save or we can check the names)
        $this->assertEquals('Session 1', $sessionsData[0]['title']);
        $this->assertEquals(1, $sessionsData[0]['messages_count']);
        
        $this->assertEquals('Session 2', $sessionsData[1]['title']);
        $this->assertEquals(0, $sessionsData[1]['messages_count']);
    }

    /** @test */
    public function authenticated_user_can_rename_chat_session()
    {
        $session = ChatSession::create(['user_id' => $this->user->id, 'title' => 'Old Title']);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/pdf/sessions/rename', [
                'chat_session_id' => $session->id,
                'title' => 'New Title'
            ], $this->headers);

        $response->assertStatus(200);
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
            'title' => 'New Title'
        ]);
    }

    /** @test */
    public function user_cannot_rename_another_users_chat_session()
    {
        $otherUser = User::factory()->create();
        $session = ChatSession::create(['user_id' => $otherUser->id, 'title' => 'Other Title']);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/pdf/sessions/rename', [
                'chat_session_id' => $session->id,
                'title' => 'Stolen Title'
            ], $this->headers);

        // Validation constraint prevents referencing other users' sessions
        $response->assertStatus(422);
    }

    /** @test */
    public function authenticated_user_can_delete_chat_session_and_messages()
    {
        $session = ChatSession::create(['user_id' => $this->user->id, 'title' => 'To Delete']);
        
        ChatHistory::create([
            'user_id' => $this->user->id,
            'chat_session_id' => $session->id,
            'question' => 'Delete Me',
            'answer' => 'Okay',
            'suggestions' => [],
            'images' => [],
            'reference_pages' => []
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/pdf/sessions/delete', [
                'chat_session_id' => $session->id
            ], $this->headers);

        $response->assertStatus(200);
        
        $this->assertDatabaseMissing('chat_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('chat_histories', ['question' => 'Delete Me']);
    }

    /** @test */
    public function asking_question_without_session_id_auto_creates_session_and_renames_it()
    {
        Http::fake([
            '*' => Http::response([
                'answer' => 'This is a mocked PDF answer',
                'suggestions' => ['suggestion A', 'suggestion B'],
                'images' => ['image.png'],
                'reference_pages' => [2]
            ], 200)
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/pdf/ask', [
                'question' => 'What is the coefficient of bank capital requirements?'
            ], $this->headers);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'answer',
            'suggestions',
            'images',
            'reference_pages',
            'chat_session_id',
            'chat_session_title',
            'message'
        ]);

        $sessionId = $response->json('chat_session_id');
        $this->assertNotNull($sessionId);
        
        // Title should be auto-generated from the question
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $sessionId,
            'title' => 'What is the coefficient of bank capital re...'
        ]);

        // Message should be saved in history and linked to session
        $this->assertDatabaseHas('chat_histories', [
            'chat_session_id' => $sessionId,
            'question' => 'What is the coefficient of bank capital requirements?',
            'answer' => 'This is a mocked PDF answer'
        ]);
    }

    /** @test */
    public function asking_question_with_existing_session_saves_to_it_and_maintains_context()
    {
        $session = ChatSession::create(['user_id' => $this->user->id, 'title' => 'Custom Chat']);

        // Store a past message
        ChatHistory::create([
            'user_id' => $this->user->id,
            'chat_session_id' => $session->id,
            'question' => 'First question',
            'answer' => 'First answer',
            'suggestions' => [],
            'images' => [],
            'reference_pages' => []
        ]);

        // Mock HTTP for the FastAPI service.
        // We will assert that the history was correctly loaded from the database and passed to FastAPI.
        Http::fake(function ($request) {
            $body = json_decode($request->body(), true);
            
            // Check that the history contains the past message
            if (isset($body['history']) && count($body['history']) === 2) {
                if ($body['history'][0]['content'] === 'First question' && $body['history'][1]['content'] === 'First answer') {
                    return Http::response([
                        'answer' => 'Second answer with context',
                        'suggestions' => [],
                        'images' => [],
                        'reference_pages' => []
                    ], 200);
                }
            }

            return Http::response(['error' => 'History context was missing or incorrect'], 500);
        });

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/pdf/ask', [
                'chat_session_id' => $session->id,
                'question' => 'Second question'
            ], $this->headers);

        $response->assertStatus(200);
        $response->assertJsonPath('answer', 'Second answer with context');

        $this->assertDatabaseHas('chat_histories', [
            'chat_session_id' => $session->id,
            'question' => 'Second question',
            'answer' => 'Second answer with context'
        ]);
        
        // Session title should not be renamed since it wasn't default 'New Chat'
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
            'title' => 'Custom Chat'
        ]);
    }

    /** @test */
    public function history_can_be_filtered_by_session_id_and_returns_in_chronological_order()
    {
        $session = ChatSession::create(['user_id' => $this->user->id, 'title' => 'Session History']);

        // Create 2 messages with different timestamps
        $msg1 = ChatHistory::create([
            'user_id' => $this->user->id,
            'chat_session_id' => $session->id,
            'question' => 'Message 1',
            'answer' => 'Reply 1',
            'suggestions' => [],
            'images' => [],
            'reference_pages' => [],
            'created_at' => now()->subMinutes(10)
        ]);

        $msg2 = ChatHistory::create([
            'user_id' => $this->user->id,
            'chat_session_id' => $session->id,
            'question' => 'Message 2',
            'answer' => 'Reply 2',
            'suggestions' => [],
            'images' => [],
            'reference_pages' => [],
            'created_at' => now()->subMinutes(5)
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/pdf/history?chat_session_id={$session->id}", $this->headers);

        $response->assertStatus(200);
        $historyData = $response->json('history.data');
        
        $this->assertCount(2, $historyData);
        // Ascending chronological order within a session
        $this->assertEquals('Message 1', $historyData[0]['question']);
        $this->assertEquals('Message 2', $historyData[1]['question']);
    }
}
