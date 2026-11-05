<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminUserCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->admin = User::create([
            'name' => 'Super',
            'surname' => 'Admin',
            'email' => 'superadmin@example.com',
            'phone_no' => '1234567890',
            'password' => Hash::make('password123'),
            'user_type' => '1',
            'status' => '1',
        ]);
    }

    /** @test */
    public function admin_can_view_create_user_page()
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('createuser'));
        $response->assertStatus(200);
        $response->assertSee('name="user_type"', false);
    }

    /** @test */
    public function admin_can_create_a_new_admin_user()
    {
        $response = $this->actingAs($this->admin, 'web')->post(route('storeuser'), [
            'name' => 'New',
            'surname' => 'Admin',
            'email' => 'newadmin@example.com',
            'phone_no' => '9876543210',
            'user_type' => '1',
            'status' => '1',
            'password' => 'secretPassword123',
        ]);

        $response->assertRedirect(route('manageusers'));
        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.com',
            'user_type' => '1',
            'business_id' => null,
        ]);
    }

    /** @test */
    public function admin_can_create_a_standard_user()
    {
        $response = $this->actingAs($this->admin, 'web')->post(route('storeuser'), [
            'name' => 'Regular',
            'surname' => 'User',
            'email' => 'regular@example.com',
            'phone_no' => '5555555555',
            'user_type' => '2',
            'status' => '1',
        ]);

        $response->assertRedirect(route('manageusers'));
        $this->assertDatabaseHas('users', [
            'email' => 'regular@example.com',
            'user_type' => '2',
        ]);
    }

    /** @test */
    public function manage_users_displays_admin_badge_and_filters()
    {
        User::create([
            'name' => 'Other',
            'surname' => 'Admin',
            'email' => 'otheradmin@example.com',
            'phone_no' => '1111111111',
            'password' => Hash::make('password123'),
            'user_type' => '1',
            'status' => '1',
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('manageusers', ['company' => 'admin']));
        $response->assertStatus(200);
        $response->assertSee('otheradmin@example.com');
    }

    /** @test */
    public function normal_user_cannot_login_as_admin()
    {
        User::create([
            'name' => 'Normal',
            'surname' => 'User',
            'email' => 'normaluser@example.com',
            'phone_no' => '2222222222',
            'password' => Hash::make('password123'),
            'user_type' => '2',
            'status' => '1',
        ]);

        $response = $this->post(route('signin'), [
            'email' => 'normaluser@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertFalse(auth()->guard('web')->check());
    }

    /** @test */
    public function normal_user_cannot_access_admin_dashboard_via_middleware()
    {
        $normalUser = User::create([
            'name' => 'Normal2',
            'surname' => 'User2',
            'email' => 'normaluser2@example.com',
            'phone_no' => '3333333333',
            'password' => Hash::make('password123'),
            'user_type' => '2',
            'status' => '1',
        ]);

        $response = $this->actingAs($normalUser, 'web')->get(route('dashboard'));
        $response->assertRedirect(route('login'));
        $this->assertFalse(auth()->guard('web')->check());
    }
}

