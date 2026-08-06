<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        $this->faker->locale('id_ID');

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password', // Laravel 11: akan di-hash oleh cast "hashed"
            'srn' => null,            // kita isi di state khusus
            'prody_id' => null,       // kita isi di state khusus
            'year' => null,
            'image' => null,
            'nilaibasiclistening' => null,
            'remember_token' => Str::random(10),
        ];
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

    public function studentForPrody(int $prodyId, string $srn): static
    {
        return $this->state(fn () => [
            'prody_id' => $prodyId,
            'srn'      => $srn,
        ]);
    }
}
