<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BankAdminBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $business;

    protected $bankAdminUser;

    protected $bankMemberUser;

    protected $individualUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super',
            'surname' => 'Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
            'user_type' => '1',
            'status' => '1',
        ]);

        $this->business = Business::create([
            'name' => 'Test Bank Pictet',
            'business_code' => 'BUS-PICTET',
            'email' => 'admin@pictet.com',
            'password' => Hash::make('password123'),
            'status' => 1,
        ]);

        // User whose email matches business admin email (Dual role: Bank Admin + Regular User)
        $this->bankAdminUser = User::create([
            'name' => 'Fabrice',
            'surname' => 'Guelat',
            'email' => 'admin@pictet.com',
            'password' => Hash::make('password123'),
            'user_type' => '2',
            'business_id' => $this->business->id,
            'status' => '1',
        ]);

        // Regular employee under the bank
        $this->bankMemberUser = User::create([
            'name' => 'Jean',
            'surname' => 'Dupont',
            'email' => 'jean.dupont@pictet.com',
            'password' => Hash::make('password123'),
            'user_type' => '2',
            'business_id' => $this->business->id,
            'status' => '1',
        ]);

        // Individual user with no bank
        $this->individualUser = User::create([
            'name' => 'Marc',
            'surname' => 'Lefevre',
            'email' => 'marc.lefevre@gmail.com',
            'password' => Hash::make('password123'),
            'user_type' => '2',
            'business_id' => null,
            'status' => '1',
        ]);
    }

    /** @test */
    public function user_model_identifies_bank_admin_correctly()
    {
        $this->assertTrue($this->bankAdminUser->isBankAdmin());
        $this->assertFalse($this->bankMemberUser->isBankAdmin());
        $this->assertFalse($this->individualUser->isBankAdmin());
    }

    /** @test */
    public function manage_users_page_displays_bank_admin_badge_and_dual_user_profile()
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('manageusers'));

        $response->assertStatus(200);

        // Bank Admin user should show Bank Admin badge and + Regular User Profile
        $response->assertSee('badge-bank-admin', false);
        $response->assertSee('admin@pictet.com');
        $response->assertSee('Test Bank Pictet');
        $response->assertSee('BUS-PICTET');

        // Regular bank member should show Bank User badge
        $response->assertSee('badge-bank-user', false);
    }

    /** @test */
    public function filter_by_bank_admins_returns_only_bank_admin_users()
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('manageusers', ['company' => 'bank_admins']));

        $response->assertStatus(200);
        $response->assertSee('admin@pictet.com');
        $response->assertDontSee('jean.dupont@pictet.com');
        $response->assertDontSee('marc.lefevre@gmail.com');
    }

    /** @test */
    public function filter_by_individual_users_excludes_bank_admins()
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('manageusers', ['company' => 'individual']));

        $response->assertStatus(200);
        $response->assertSee('Marc Lefevre');
        $response->assertDontSee('Fabrice Guelat');
        $response->assertDontSee('Jean Dupont');
    }
}
