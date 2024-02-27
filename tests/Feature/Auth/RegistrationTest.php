<?php

namespace Tests\Feature\Auth;

use App\Models\Invitation;
use App\Models\User;
use Faker\Factory;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\MigrateFreshSeedOnce;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use MigrateFreshSeedOnce;

    protected bool $seed = true;

    public function test_first_user_can_register_as_super_admin(): void
    {
        $response = $this->post('/register', [
            'name' => 'First User',
            'phone' => '081234567891',
            'email' => 'first@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertNoContent();

        $firstUser = User::where('email', 'first@example.com')->first();
        $this->actingAs($firstUser);

        $response = $this->getJson('/api/v1/user');

        $response->assertJson([
            'data' => [
                'role' => 'Super Admin',
            ],
        ]);
    }

    public function test_new_user_can_register_with_invite_code(): void
    {
        $invitation = Invitation::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Invited User',
            'phone' => '081234567892',
            'email' => $invitation->email,
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite_token' => $invitation->invite_token
        ]);

        $this->assertAuthenticated();
        $response->assertNoContent();

        $this->assertTrue(User::where('email', $invitation->email)->first()->hasRole($invitation->role));
        $this->assertTrue(Invitation::where('email', $invitation->email)->first()->accepted);
    }

    public function test_new_user_cannot_register_without_invite_code(): void
    {
        $response = $this->post('/register', [
            'name' => 'Second User',
            'phone' => '081234567893',
            'email' => 'second@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertForbidden();
    }

    public function test_new_user_cannot_register_with_invalid_invite_code(): void
    {
        $response = $this->post('/register', [
            'name' => 'Second User',
            'phone' => '081234567894',
            'email' => 'guest@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite_token' => 'wrong-invite-token'
        ]);

        $response->assertForbidden();
    }

    public function test_invited_user_cannot_register_with_different_email(): void
    {
        $invitation = Invitation::factory()->create();
        $faker = Factory::create();

        do {
            $differentEmail = $faker->safeEmail;
        } while ($differentEmail === $invitation->email);

        $response = $this->post('/register', [
            'name' => 'Invited User',
            'phone' => '081234567893',
            'email' => $differentEmail,
            'password' => 'password',
            'password_confirmation' => 'password',
            'invite_token' => $invitation->invite_token,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 422,
                'status' => 'Unprocessable Entity',
                'errors' => [
                    'email' => ["Kode undangan tidak valid untuk alamat email {$differentEmail}."],
                ],
            ]);
    }
}
