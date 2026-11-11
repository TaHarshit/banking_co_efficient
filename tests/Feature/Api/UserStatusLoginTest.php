<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserStatusLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_allows_login_for_active_user()
    {
        $user = User::create([
            'name' => 'Active User',
            'username' => 'activeuser',
            'email' => 'active@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'user_type' => '2',
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'active@example.com',
            'password' => 'password123',
            'platform' => 'WEB',
            'device_token' => 'test-token',
        ], [
            'api-key' => 'BANKING-CO-EFFICIENT',
            'platform' => 'WEB',
        ]);

        if ($response->status() !== 200) {
            $response->dump();
        }
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'You have successfully login!');
        $this->assertEquals('active', $response->json('data.status'));
    }

    /** @test */
    public function it_restricts_login_for_pending_user()
    {
        $user = User::create([
            'name' => 'Pending User',
            'username' => 'pendinguser',
            'email' => 'pending@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
            'user_type' => '2',
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'pending@example.com',
            'password' => 'password123',
            'platform' => 'WEB',
            'device_token' => 'test-token',
        ], [
            'api-key' => 'BANKING-CO-EFFICIENT',
            'platform' => 'WEB',
        ]);

        $response->assertStatus(422); // VALIDATION_ERROR returns 422
        $response->assertJsonPath('message', 'Your account is pending approval from the business administrator.');
    }

    /** @test */
    public function it_restricts_login_for_rejected_user()
    {
        $user = User::create([
            'name' => 'Rejected User',
            'username' => 'rejecteduser',
            'email' => 'rejected@example.com',
            'password' => Hash::make('password123'),
            'status' => 'rejected',
            'user_type' => '2',
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'rejected@example.com',
            'password' => 'password123',
            'platform' => 'WEB',
            'device_token' => 'test-token',
        ], [
            'api-key' => 'BANKING-CO-EFFICIENT',
            'platform' => 'WEB',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Your account has been rejected. Please contact the business administrator.');
    }

    /** @test */
    public function it_restricts_login_for_deleted_user()
    {
        $user = User::create([
            'name' => 'Deleted User',
            'username' => 'deleteduser',
            'email' => 'deleted@example.com',
            'password' => Hash::make('password123'),
            'status' => 'deleted',
            'user_type' => '2',
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'deleted@example.com',
            'password' => 'password123',
            'platform' => 'WEB',
            'device_token' => 'test-token',
        ], [
            'api-key' => 'BANKING-CO-EFFICIENT',
            'platform' => 'WEB',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'This account has been deleted.');
    }

    /** @test */
    public function it_allows_admin_user_to_login_on_mobile_app_for_testing()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'user_type' => '1',
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
            'platform' => 'IOS',
            'device_token' => 'test-token-ios',
        ], [
            'api-key' => 'BANKING-CO-EFFICIENT',
            'platform' => 'IOS',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'You have successfully login!');
        $this->assertEquals('1', (string)$response->json('data.user_type'));
        $this->assertTrue($admin->fresh()->isUnlimited());
        $this->assertTrue($admin->fresh()->isSubscriptionActive());
    }
}
