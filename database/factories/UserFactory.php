<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Faker\Generator as Faker;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = app(Faker::class);

        $mobileNetworkPrefixes = [
            '81', '82', '85', '87', '88', '89',
        ];

        return [
            'name' => fake()->name(),
            'phone' => $this->generateIndonesianPhoneNumber($faker, $mobileNetworkPrefixes),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    private function generateIndonesianPhoneNumber(Faker $faker, array $mobileNetworkPrefixes): string
    {
        $prefix = $faker->randomElement($mobileNetworkPrefixes);
        $subscriberNumber = $faker->randomNumber(8, true);

        return "+62$prefix$subscriberNumber";
    }

    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Staff');
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withRole(string $roleName)
    {
        return $this->afterCreating(function (User $user) use ($roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->assignRole($role);
            }
        });
    }
}
