<?php

namespace Tests\Feature\Api;

use App\Models\CaseStudyQuestion;
use App\Models\CaseStudyQuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseStudySectionsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_returns_all_case_study_sections_grouped_with_questions()
    {
        // Create mock data
        $question1 = CaseStudyQuestion::create([
            'section_name' => 'Section A',
            'section_name_en' => 'Section A',
            'section_name_fr' => 'Section A (FR)',
            'question_en' => 'English Question 1',
            'question_fr' => 'French Question 1',
        ]);

        $question1->options()->create([
            'option_en' => 'English Option 1',
            'option_fr' => 'French Option 1',
            'is_correct' => true,
        ]);

        $question2 = CaseStudyQuestion::create([
            'section_name' => 'Section B',
            'section_name_en' => 'Section B',
            'section_name_fr' => 'Section B (FR)',
            'question_en' => 'English Question 2',
            'question_fr' => 'French Question 2',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/case-study-sections', [
                'Accept-Language' => 'en',
                'api-key' => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => [
                    'section_name',
                    'questions' => [
                        '*' => [
                            'id',
                            'question',
                            'options' => [
                                '*' => [
                                    'id',
                                    'option_text',
                                    'is_correct',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Section A', $data[0]['section_name']);
        $this->assertEquals('English Question 1', $data[0]['questions'][0]['question']);
    }

    /** @test */
    public function it_handles_french_locale_correctly()
    {
        $question = CaseStudyQuestion::create([
            'section_name' => 'Section A',
            'section_name_en' => 'Section A',
            'section_name_fr' => 'Section A French',
            'question_en' => 'English Question',
            'question_fr' => 'French Question',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/case-study-sections', [
                'Accept-Language' => 'fr',
                'api-key' => 'BANKING-CO-EFFICIENT',
                'platform' => 'WEB',
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Section A French', $data[0]['section_name']);
        $this->assertEquals('French Question', $data[0]['questions'][0]['question']);
    }
}
