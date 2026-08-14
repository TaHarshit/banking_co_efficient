<?php

namespace Tests\Feature\Api;

use App\Models\ClientCase;
use App\Models\User;
use App\Repositories\Api\ClientCaseRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClientCaseClientIdTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_create_a_case_study_with_client_id()
    {
        $payload = [
            'client_id'        => 'CLI-2026-99',
            'client_alias'     => 'Acme Global',
            'case_reference'   => 'REF-1001',
            'context_overview' => 'High stakes contract renegotiation',
            'case_details'     => [
                'objectives' => 'Lower fees by 15%',
            ],
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/client-cases', $payload, [
                'api-key'  => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('client_cases', [
            'user_id'      => $this->user->id,
            'client_id'    => 'CLI-2026-99',
            'client_alias' => 'Acme Global',
        ]);

        $responseData = $response->json('data');
        $this->assertEquals('CLI-2026-99', $responseData['client_id']);
    }

    /** @test */
    public function it_can_update_a_case_study_client_id()
    {
        $case = ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLI-OLD',
            'client_alias' => 'Old Client',
        ]);

        $payload = [
            'id'           => $case->id,
            'client_id'    => 'CLI-NEW',
            'client_alias' => 'Updated Client',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/client-cases', $payload, [
                'api-key'  => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('client_cases', [
            'id'           => $case->id,
            'client_id'    => 'CLI-NEW',
            'client_alias' => 'Updated Client',
        ]);
    }

    /** @test */
    public function it_can_filter_cases_by_client_id()
    {
        ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLIENT-AAA',
            'client_alias' => 'Alpha Inc',
        ]);

        ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLIENT-BBB',
            'client_alias' => 'Beta LLC',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/client-cases?client_id=CLIENT-AAA', [
                'api-key'  => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('CLIENT-AAA', $data[0]['client_id']);
    }

    /** @test */
    public function it_fetches_previous_client_cases_for_ai_reference()
    {
        $repo = app(ClientCaseRepository::class);

        $case1 = ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLIENT-XYZ',
            'client_alias' => 'XYZ Corp',
            'ai_analysis'  => ['ai_recommendations' => ['Anchor high']],
            'action_plan'  => ['executive_summary' => 'Initial plan'],
        ]);

        $case2 = ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLIENT-XYZ',
            'client_alias' => 'XYZ Corp Phase 2',
        ]);

        $previousCases = $repo->getPreviousClientCases($this->user->id, 'CLIENT-XYZ', $case2->id);

        $this->assertCount(1, $previousCases);
        $this->assertEquals($case1->id, $previousCases->first()->id);
    }

    /** @test */
    public function it_can_get_distinct_clients_dropdown_list()
    {
        ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLIENT-001',
            'client_alias' => 'Alpha Corp Initial',
        ]);

        ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLIENT-001',
            'client_alias' => 'Alpha Corp Updated',
        ]);

        ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLIENT-002',
            'client_alias' => 'Beta LLC',
        ]);

        // Another user's client (should not appear)
        $otherUser = User::factory()->create();
        ClientCase::create([
            'user_id'      => $otherUser->id,
            'client_id'    => 'CLIENT-OTHER',
            'client_alias' => 'Other Corp',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/client-cases/clients', [
                'api-key'  => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);
        $clientIds = array_column($data, 'client_id');
        $this->assertContains('CLIENT-001', $clientIds);
        $this->assertContains('CLIENT-002', $clientIds);
        $this->assertNotContains('CLIENT-OTHER', $clientIds);

        // Find CLIENT-001 and verify total_cases count is 2
        $client001 = collect($data)->firstWhere('client_id', 'CLIENT-001');
        $this->assertEquals(2, $client001['total_cases']);
    }

    /** @test */
    public function it_can_check_client_id_when_available()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/client-cases/check-client-id?client_id=CLI-NEW-99', [
                'api-key'  => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('CLI-NEW-99', $data['client_id']);
        $this->assertFalse($data['exists']);
        $this->assertFalse($data['is_used']);
        $this->assertEquals(0, $data['total_cases']);
    }

    /** @test */
    public function it_can_check_client_id_when_already_in_use()
    {
        ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLI-EXISTING',
            'client_alias' => 'Existing Company Inc',
        ]);

        ClientCase::create([
            'user_id'      => $this->user->id,
            'client_id'    => 'CLI-EXISTING',
            'client_alias' => 'Existing Company Inc Phase 2',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/client-cases/check-client-id?client_id=CLI-EXISTING', [
                'api-key'  => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('CLI-EXISTING', $data['client_id']);
        $this->assertTrue($data['exists']);
        $this->assertTrue($data['is_used']);
        $this->assertEquals(2, $data['total_cases']);
        $this->assertNotEmpty($data['client_alias']);
    }

    /** @test */
    public function it_validates_empty_client_id_in_check_api()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/client-cases/check-client-id', [
                'api-key'  => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(422);
    }
}
