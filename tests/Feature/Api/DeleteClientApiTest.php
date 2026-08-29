<?php

namespace Tests\Feature\Api;

use App\Models\AiJob;
use App\Models\Client;
use App\Models\ClientCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeleteClientApiTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_delete_a_client_and_its_cases_by_numeric_id()
    {
        $client = Client::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLI-2026-DEL1',
            'client_alias' => 'Test Delete Client 1',
            'notes'        => 'Temporary test client',
        ]);

        $case1 = ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLI-2026-DEL1',
            'client_alias' => 'Test Delete Client 1',
            'case_reference' => 'REF-DEL-1',
        ]);

        $case2 = ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLI-2026-DEL1',
            'client_alias' => 'Test Delete Client 1',
            'case_reference' => 'REF-DEL-2',
        ]);

        $job = AiJob::create([
            'user_id'  => $this->user->id,
            'case_id'  => $case1->id,
            'job_type' => 'analyze_case',
            'status'   => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/clients/{$client->id}", [], [
                'api-key'  => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'SUCCESS',
        ]);

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertDatabaseMissing('client_cases', ['id' => $case1->id]);
        $this->assertDatabaseMissing('client_cases', ['id' => $case2->id]);
        $this->assertDatabaseMissing('ai_jobs', ['id' => $job->id]);
    }

    /** @test */
    public function it_can_delete_a_client_and_its_cases_by_string_client_id()
    {
        $client = Client::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLI-STRING-99',
            'client_alias' => 'String Client',
        ]);

        $case = ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLI-STRING-99',
            'client_alias' => 'String Client',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/delete-client/CLI-STRING-99", [], [
                'api-key'  => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'SUCCESS',
        ]);

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertDatabaseMissing('client_cases', ['id' => $case->id]);
    }

    /** @test */
    public function it_prevents_deleting_another_users_client()
    {
        $otherUser = User::factory()->create();

        $client = Client::create([
            'user_id'      => $otherUser->id,
            'client_id'    => 'CLI-OTHER-USER',
            'client_alias' => 'Other Client',
        ]);

        $case = ClientCase::create([
            'user_id'      => $otherUser->id,
            'client_id'    => 'CLI-OTHER-USER',
            'client_alias' => 'Other Client',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/clients/{$client->id}", [], [
                'api-key'  => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
        $this->assertDatabaseHas('client_cases', ['id' => $case->id]);
    }
}
