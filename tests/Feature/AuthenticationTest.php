<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Facility;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_register(): void
    {
        $facility = Facility::first();
        
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+250788123999',
            'role' => 'staff',
            'facility_id' => $facility->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id', 'name', 'email', 'phone', 'role', 'facility_id', 'facility'
                ],
                'token',
                'message'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'staff',
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = User::where('email', 'admin@efiche.rw')->first();
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@efiche.rw',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id', 'name', 'email', 'phone', 'role', 'facility_id', 'facility'
                ],
                'token',
                'message'
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@efiche.rw',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid credentials',
                'message' => 'The provided credentials are incorrect.'
            ]);
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('email', 'admin@efiche.rw')->first();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully'
            ]);
    }

    public function test_user_can_get_profile(): void
    {
        $user = User::where('email', 'admin@efiche.rw')->first();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/auth/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id', 'name', 'email', 'phone', 'role', 'facility_id', 'facility', 'is_active', 'created_at'
                ]
            ]);
    }

    public function test_protected_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/invoices');
        $response->assertStatus(401);
        
        $response = $this->getJson('/api/dashboard/stats');
        $response->assertStatus(401);
        
        $response = $this->getJson('/api/payments');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_protected_routes(): void
    {
        $user = User::where('email', 'admin@efiche.rw')->first();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/invoices');
        $response->assertStatus(200);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/dashboard/stats');
        $response->assertStatus(200);
    }
}
