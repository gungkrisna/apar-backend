<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $email = $this->faker->unique()->safeEmail;
        $role = $this->faker->randomElement(['Staff', 'Super Admin']);


        return [
            'inviter_id' => User::factory(),
            'invite_token' => Invitation::generateInvitationToken($this->faker->unique()->safeEmail),
            'email' => $email,
            'role' => $role,
            'expired_at' => now()->addDay(),
            'accepted' => false,
        ];
    }
}
