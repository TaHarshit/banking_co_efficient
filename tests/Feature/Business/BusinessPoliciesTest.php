<?php

namespace Tests\Feature\Business;

use App\Models\Business;
use App\Models\CaseStudyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BusinessPoliciesTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;
    protected Business $otherBusiness;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->business = Business::create([
            'name' => 'Bank Alpha',
            'email' => 'alpha_' . uniqid() . '@bank.com',
            'password' => 'Secret123!',
            'status' => 1,
        ]);

        $this->otherBusiness = Business::create([
            'name' => 'Bank Beta',
            'email' => 'beta_' . uniqid() . '@bank.com',
            'password' => 'Secret123!',
            'status' => 1,
        ]);
    }

    /** @test */
    public function business_admin_can_view_business_policies_index()
    {
        $response = $this->actingAs($this->business, 'business')
            ->get(route('business.policies.index'));

        $response->assertStatus(200);
        $response->assertSee('Business Policies');
        $response->assertSee('Internal Business Policies Message');
    }

    /** @test */
    public function business_admin_can_update_internal_policies_message()
    {
        $messageText = 'Please verify that the client conforms to Bank Alpha Internal Compliance Rule 2026.';

        $response = $this->actingAs($this->business, 'business')
            ->post(route('business.policies.message.update'), [
                'business_policies_message' => $messageText,
            ]);

        $response->assertRedirect(route('business.policies.index'));
        $this->business->refresh();
        $this->assertEquals($messageText, $this->business->business_policies_message);
    }

    /** @test */
    public function business_admin_can_create_custom_policy_question()
    {
        $payload = [
            'section_name_en' => 'Alpha Risk Assessment',
            'section_name_fr' => 'Évaluation des risques Alpha',
            'question_en' => 'Does the applicant meet minimum liquidity requirements?',
            'question_fr' => 'Le candidat répond-il aux exigences minimales de liquidité?',
            'options' => [
                ['en' => 'Yes, exceeds threshold', 'fr' => 'Oui, dépasse le seuil', 'is_correct' => 1],
                ['en' => 'No, falls below', 'fr' => 'Non, inférieur', 'is_correct' => 0],
            ],
        ];

        $response = $this->actingAs($this->business, 'business')
            ->post(route('business.policies.store'), $payload);

        $response->assertRedirect(route('business.policies.index'));

        $question = CaseStudyQuestion::where('business_id', $this->business->id)->first();
        $this->assertNotNull($question);
        $this->assertEquals('Alpha Risk Assessment', $question->section_name_en);
        $this->assertCount(2, $question->options);
    }

    /** @test */
    public function business_admin_can_update_and_delete_their_policy_question()
    {
        $question = CaseStudyQuestion::create([
            'business_id' => $this->business->id,
            'section_name' => 'Initial Section',
            'section_name_en' => 'Initial Section',
            'section_name_fr' => 'Section Initiale',
            'question_en' => 'Initial Question',
            'question_fr' => 'Question Initiale',
        ]);

        $question->options()->create([
            'option_en' => 'Opt 1',
            'option_fr' => 'Opt 1 FR',
            'is_correct' => true,
        ]);

        // Update
        $response = $this->actingAs($this->business, 'business')
            ->post(route('business.policies.update', $question->id), [
                'section_name_en' => 'Updated Section',
                'section_name_fr' => 'Section Mise à jour',
                'question_en' => 'Updated Question EN',
                'question_fr' => 'Updated Question FR',
                'options' => [
                    ['en' => 'New Opt 1', 'fr' => 'Nouv Opt 1', 'is_correct' => 1],
                ],
            ]);

        $response->assertRedirect(route('business.policies.index'));
        $question->refresh();
        $this->assertEquals('Updated Section', $question->section_name_en);
        $this->assertEquals('Updated Question EN', $question->question_en);

        // Delete
        $deleteResponse = $this->actingAs($this->business, 'business')
            ->get(route('business.policies.destroy', $question->id));

        $deleteResponse->assertRedirect(route('business.policies.index'));
        $this->assertDatabaseMissing('case_study_questions', ['id' => $question->id]);
    }

    /** @test */
    public function business_cannot_modify_or_delete_another_business_policy_question()
    {
        $otherQuestion = CaseStudyQuestion::create([
            'business_id' => $this->otherBusiness->id,
            'section_name' => 'Beta Section',
            'section_name_en' => 'Beta Section',
            'section_name_fr' => 'Beta Section FR',
            'question_en' => 'Beta Question',
            'question_fr' => 'Beta Question FR',
        ]);

        // Bank Alpha tries to edit Bank Beta's question -> 404
        $response = $this->actingAs($this->business, 'business')
            ->get(route('business.policies.edit', $otherQuestion->id));
        $response->assertStatus(404);

        // Bank Alpha tries to delete Bank Beta's question -> 404
        $deleteResponse = $this->actingAs($this->business, 'business')
            ->get(route('business.policies.destroy', $otherQuestion->id));
        $deleteResponse->assertStatus(404);

        $this->assertDatabaseHas('case_study_questions', ['id' => $otherQuestion->id]);
    }

    /** @test */
    public function api_returns_bank_specific_policy_message_and_scoped_questions()
    {
        // Set policy message on Alpha
        $this->business->update([
            'business_policies_message' => 'Alpha Custom Policy Notice: KYC must be pre-approved.',
        ]);

        // Create custom question for Alpha
        $alphaQuestion = CaseStudyQuestion::create([
            'business_id' => $this->business->id,
            'section_name' => 'Alpha Section',
            'section_name_en' => 'Alpha Section',
            'section_name_fr' => 'Alpha Section FR',
            'question_en' => 'Alpha specific policy question',
            'question_fr' => 'Question specifique Alpha',
        ]);
        $alphaQuestion->options()->create([
            'option_en' => 'Option Alpha 1',
            'option_fr' => 'Option Alpha 1 FR',
            'is_correct' => true,
        ]);

        // Create global question
        $globalQuestion = CaseStudyQuestion::create([
            'business_id' => null,
            'section_name' => 'Global Section',
            'section_name_en' => 'Global Section',
            'section_name_fr' => 'Global Section FR',
            'question_en' => 'Global policy question',
            'question_fr' => 'Question globale',
        ]);

        // Create user belonging to Alpha
        $alphaUser = User::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->actingAs($alphaUser, 'api')
            ->getJson('/api/case-study-sections', [
                'Accept-Language' => 'en',
                'api-key' => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Alpha Custom Policy Notice: KYC must be pre-approved.', $response->json('policy_message'));

        $data = $response->json('data');
        $sectionNames = collect($data)->pluck('section_name')->all();
        $this->assertContains('Alpha Section', $sectionNames);
        // Alpha has custom questions, so it should not include the global section
        $this->assertNotContains('Global Section', $sectionNames);
    }

    /** @test */
    public function api_falls_back_to_global_questions_when_business_has_no_custom_questions()
    {
        // Ensure settings has no global policy message for this test
        $settings = \App\Models\Settings::first();
        if ($settings) {
            $settings->update(['policy_sections' => null, 'business_policies_message' => null]);
        }

        // Business Beta has no custom questions and no policy message
        $betaUser = User::factory()->create([
            'business_id' => $this->otherBusiness->id,
        ]);

        $globalQuestion = CaseStudyQuestion::create([
            'business_id' => null,
            'section_name' => 'Global Fallback Section',
            'section_name_en' => 'Global Fallback Section',
            'section_name_fr' => 'Section globale repli',
            'question_en' => 'Global fallback question',
            'question_fr' => 'Question globale repli',
        ]);

        $response = $this->actingAs($betaUser, 'api')
            ->getJson('/api/case-study-sections', [
                'Accept-Language' => 'en',
                'api-key' => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $this->assertNull($response->json('policy_message'));

        $data = $response->json('data');
        $sectionNames = collect($data)->pluck('section_name')->all();
        $this->assertContains('Global Fallback Section', $sectionNames);
    }

    /** @test */
    public function business_admin_can_update_three_policy_sections_with_point_messages()
    {
        $payload = [
            'sections' => [
                [
                    'title' => 'KYC & Client Verification',
                    'points' => [
                        'Verify primary identity document',
                        'Check address proof validity within 3 months',
                    ],
                ],
                [
                    'title' => 'Risk & Compliance Rules',
                    'points' => [
                        'Review source of wealth and funds',
                    ],
                ],
                [
                    'title' => 'Final Submission Checklist',
                    'points' => [
                        'Obtain branch manager sign-off',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->business, 'business')
            ->post(route('business.policies.message.update'), $payload);

        $response->assertRedirect(route('business.policies.index'));
        $this->business->refresh();

        $this->assertIsArray($this->business->policy_sections);
        $this->assertCount(3, $this->business->policy_sections);
        $this->assertEquals('KYC & Client Verification', $this->business->policy_sections[0]['title']);
        $this->assertCount(2, $this->business->policy_sections[0]['points']);
        $this->assertStringContainsString('KYC & Client Verification', $this->business->business_policies_message);
        $this->assertStringContainsString('Verify primary identity document', $this->business->business_policies_message);
    }

    /** @test */
    public function api_returns_three_structured_policy_sections_for_business_user()
    {
        $this->business->update([
            'policy_sections' => [
                ['id' => 1, 'title' => 'Section 1 Guide', 'points' => ['Point 1A', 'Point 1B']],
                ['id' => 2, 'title' => 'Section 2 Rules', 'points' => ['Point 2A']],
                ['id' => 3, 'title' => 'Section 3 Checklist', 'points' => ['Point 3A']],
            ],
            'business_policies_message' => 'Formatted message summary',
        ]);

        $alphaUser = User::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->actingAs($alphaUser, 'api')
            ->getJson('/api/case-study-sections', [
                'Accept-Language' => 'en',
                'api-key' => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $sections = $response->json('policy_sections');
        $this->assertIsArray($sections);
        $this->assertCount(3, $sections);
        $this->assertEquals('Section 1 Guide', $sections[0]['title']);
        $this->assertEquals(['Point 1A', 'Point 1B'], $sections[0]['points']);
    }

    /** @test */
    public function api_returns_localized_french_policy_sections_when_requested()
    {
        $this->business->update([
            'policy_sections' => [
                [
                    'id' => 1,
                    'title_en' => 'Section 1 Guide',
                    'title_fr' => 'Guide de la section 1',
                    'points' => [
                        ['en' => 'Point 1A English', 'fr' => 'Point 1A Français'],
                    ],
                ],
                [
                    'id' => 2,
                    'title_en' => 'Section 2 Rules',
                    'title_fr' => 'Règles de la section 2',
                    'points' => [
                        ['en' => 'Point 2A English', 'fr' => 'Point 2A Français'],
                    ],
                ],
                [
                    'id' => 3,
                    'title_en' => 'Section 3 Checklist',
                    'title_fr' => 'Liste de la section 3',
                    'points' => [
                        ['en' => 'Point 3A English', 'fr' => 'Point 3A Français'],
                    ],
                ],
            ],
            'business_policies_message' => 'English summary message',
        ]);

        $alphaUser = User::factory()->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->actingAs($alphaUser, 'api')
            ->getJson('/api/case-study-sections?lang=fr', [
                'Accept-Language' => 'fr',
                'api-key' => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $sections = $response->json('policy_sections');
        $this->assertIsArray($sections);
        $this->assertCount(3, $sections);
        $this->assertEquals('Guide de la section 1', $sections[0]['title']);
        $this->assertEquals(['Point 1A Français'], $sections[0]['points']);
    }

    /** @test */
    public function individual_user_receives_global_default_policy_sections()
    {
        $settings = \App\Models\Settings::first() ?? \App\Models\Settings::create([]);
        $settings->update([
            'policy_sections' => [
                [
                    'id' => 1,
                    'title_en' => 'Global Verification Policy',
                    'title_fr' => 'Politique Globale de Vérification',
                    'points' => [
                        ['en' => 'All users must verify primary identity', 'fr' => 'Tous les utilisateurs doivent vérifier l\'identité'],
                    ],
                ],
            ],
            'business_policies_message' => 'Global message notice',
        ]);

        $individualUser = User::factory()->create([
            'business_id' => null,
        ]);

        $response = $this->actingAs($individualUser, 'api')
            ->getJson('/api/case-study-sections?lang=en', [
                'api-key' => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $sections = $response->json('policy_sections');
        $this->assertIsArray($sections);
        $this->assertCount(1, $sections);
        $this->assertEquals('Global Verification Policy', $sections[0]['title']);
        $this->assertEquals(['All users must verify primary identity'], $sections[0]['points']);
    }

    /** @test */
    public function business_user_without_custom_policies_falls_back_to_global_default()
    {
        $settings = \App\Models\Settings::first() ?? \App\Models\Settings::create([]);
        $settings->update([
            'policy_sections' => [
                [
                    'id' => 1,
                    'title_en' => 'Global Fallback Policy',
                    'title_fr' => 'Politique Globale de Repli',
                    'points' => [
                        ['en' => 'Default rule for unconfigured banks', 'fr' => 'Règle par défaut'],
                    ],
                ],
            ],
        ]);

        $emptyBank = Business::create([
            'name' => 'Bank Without Policies',
            'email' => 'empty_' . uniqid() . '@bank.com',
            'password' => 'Secret123!',
            'status' => 1,
            'policy_sections' => null,
            'business_policies_message' => null,
        ]);

        $user = User::factory()->create([
            'business_id' => $emptyBank->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/case-study-sections?lang=en', [
                'api-key' => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $sections = $response->json('policy_sections');
        $this->assertIsArray($sections);
        $this->assertCount(1, $sections);
        $this->assertEquals('Global Fallback Policy', $sections[0]['title']);
    }

    /** @test */
    public function super_admin_can_update_global_policy_sections()
    {
        $adminUser = User::factory()->create();

        $payload = [
            'target_business_id' => 'global',
            'sections' => [
                [
                    'title_en' => 'Admin Set Global Section 1',
                    'title_fr' => 'Section Globale 1 par Admin',
                    'points' => [
                        ['en' => 'Global point 1', 'fr' => 'Point global 1'],
                    ],
                ],
                [
                    'title_en' => 'Admin Set Global Section 2',
                    'title_fr' => 'Section Globale 2 par Admin',
                    'points' => [],
                ],
                [
                    'title_en' => 'Admin Set Global Section 3',
                    'title_fr' => 'Section Globale 3 par Admin',
                    'points' => [],
                ],
            ],
        ];

        $response = $this->actingAs($adminUser, 'web')
            ->post(route('admin.case_study_questions.policy_sections.update'), $payload);

        $response->assertSessionHas('success');

        $settings = \App\Models\Settings::first();
        $this->assertNotNull($settings);
        $this->assertIsArray($settings->policy_sections);
        $this->assertEquals('Admin Set Global Section 1', $settings->policy_sections[0]['title']);
    }
}
