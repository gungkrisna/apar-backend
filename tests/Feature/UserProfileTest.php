<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_users_can_access_own_profile_when_logged_in(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'phone',
                    'email',
                    'photo',
                    'email_verified_at',
                    'role',
                    'permissions',
                    'created_at',
                    'updated_at',
                ],
            ]);

            $response->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'photo' => $user->photo,
                    'email_verified_at' => $user->email_verified_at->toISOString(),
                    'role' => $user->getRoleNames()[0] ?? null,
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'created_at' => $user->created_at->toISOString(),
                    'updated_at' => $user->updated_at->toISOString(),
                ],
            ]);
    }

    public function test_users_cannot_access_own_profile_when_logged_out(): void
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }
}
