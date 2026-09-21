<?php

namespace Tests\Feature\Api;

use App\Models\ClientCase;
use App\Models\Plans;
use App\Models\User;
use App\Models\UserSubscriptions;
use Carbon\Carbon;
use Tests\TestCase;

class PlanEnforcementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_defaults_to_explorer_free_tier_with_3_analyses()
    {
        $user = new User([
            'name' => 'Free Explorer User',
            'email' => 'free_explorer@example.com',
            'free_analyses_used' => 0,
            'paid_analyses_credits' => 0,
        ]);

        $this->assertEquals(3, $user->getRemainingFreeAnalyses());
        $this->assertEquals(0, $user->getPaidCredits());
        $this->assertEquals(3, $user->getTotalAvailableAnalyses());
        $this->assertTrue($user->canRunAnalysis());
        $this->assertFalse($user->isUnlimited());
        $this->assertEquals('Explorer', $user->getActivePlan()?->name);
    }

    /** @test */
    public function it_enforces_free_analysis_limit_after_3_used()
    {
        $user = new User([
            'name' => 'Exhausted Free User',
            'email' => 'exhausted@example.com',
            'free_analyses_used' => 3,
            'paid_analyses_credits' => 0,
        ]);

        $this->assertEquals(0, $user->getRemainingFreeAnalyses());
        $this->assertEquals(0, $user->getPaidCredits());
        $this->assertEquals(0, $user->getTotalAvailableAnalyses());
        $this->assertFalse($user->canRunAnalysis());
    }

    /** @test */
    public function it_blocks_pdf_export_for_free_tier_case()
    {
        $user = new User([
            'name' => 'Free User',
            'email' => 'free_export_test@example.com',
            'free_analyses_used' => 1,
            'paid_analyses_credits' => 0,
        ]);

        $freeCase = new ClientCase([
            'can_export_pdf' => false,
            'is_full_profile' => false,
        ]);

        $this->assertFalse($user->canExportCasePdf($freeCase));
        $this->assertFalse($user->canExportSummaryPdf());
    }

    /** @test */
    public function it_allows_pdf_export_for_case_analyzed_with_single_credit()
    {
        $user = new User([
            'name' => 'Single Credit User',
            'email' => 'single_credit@example.com',
            'free_analyses_used' => 3,
            'paid_analyses_credits' => 1,
        ]);

        // When user has 1 credit, canRunAnalysis is true
        $this->assertTrue($user->canRunAnalysis());
        $this->assertEquals(1, $user->getTotalAvailableAnalyses());
        $this->assertEquals('Single Analysis', $user->getActivePlan()?->name);

        // Case analyzed with paid credit has can_export_pdf = true
        $paidCase = new ClientCase([
            'can_export_pdf' => true,
            'is_full_profile' => true,
        ]);

        $this->assertTrue($user->canExportCasePdf($paidCase));
    }

    /** @test */
    public function it_allows_unlimited_generation_and_export_for_pro_user()
    {
        $user = User::create([
            'name' => 'Pro User',
            'username' => 'prouser_' . time(),
            'email' => 'pro_' . time() . '@example.com',
            'password' => bcrypt('password'),
            'free_analyses_used' => 3,
            'paid_analyses_credits' => 0,
            'status' => 'active',
        ]);

        $proPlan = Plans::where('name', 'like', '%Pro (Monthly)%')->first()
            ?? Plans::create([
                'name' => 'Negomaster Pro (Monthly)',
                'price' => 19.00,
                'validity' => 1,
                'validity_type' => 'month',
                'type' => 0,
                'status' => 1,
            ]);

        UserSubscriptions::create([
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'subscription_start_date' => Carbon::now()->subDays(2),
            'subscription_end_date' => Carbon::now()->addDays(28),
            'status' => 1,
            'payment_status' => 'completed',
        ]);

        $this->assertTrue($user->isUnlimited());
        $this->assertTrue($user->canRunAnalysis());
        $this->assertNull($user->getTotalAvailableAnalyses());

        $anyCase = new ClientCase([
            'can_export_pdf' => false,
            'is_full_profile' => false,
        ]);

        // Pro user can export any case regardless of case stamp
        $this->assertTrue($user->canExportCasePdf($anyCase));
        $this->assertTrue($user->canExportSummaryPdf());
    }
}
